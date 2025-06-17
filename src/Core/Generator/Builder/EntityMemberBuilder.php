<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use MyCLabs\Enum\Enum;
use PhpParser\Comment;
use PhpParser\Node;
use Ramsey\Uuid\UuidInterface;
use ReflectionProperty;
use Unicorn\Enum\BasicState;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\ServerTimeCast;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column as DbColumn;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\Event\EventAwareInterface;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\Cast;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\JsonObject;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Attributes\UUIDBin;
use Windwalker\ORM\Metadata\EntityMetadata;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\Symbol;
use Windwalker\Utilities\TypeCast;

/**
 * The EntityMemberBuilder class.
 */
class EntityMemberBuilder extends AbstractAstBuilder implements EventAwareInterface
{
    use MessageOutputTrait;
    use CoreEventAwareTrait;
    use InstanceCacheTrait;
    use EntityAccessorConcernTrait;
    use EntityHooksConcernTrait;

    protected array $uses = [];

    protected array $addedUses = [];

    protected array $functionUses = [];

    protected array $addedFunctionUses = [];

    protected array $newEnums = [];

    /**
     * EntityMemberBuilder constructor.
     */
    public function __construct(protected EntityMetadata $metadata)
    {
    }

    public function getORM(): ORM
    {
        return $this->metadata->getORM();
    }

    public function process(array $options = [], ?array &$addedMembers = null): string
    {
        // Get properties
        $ref = $this->metadata->getReflector();
        $class = $this->metadata->getClassName();
        // $props = $this->metadata->getProperties();
        /** @var ReflectionProperty $lastProp */
        // $lastProp = $props[array_key_last($props)];
        [$create, $delete, $keep] = $this->getColumnsDiff();

        $addedMembers = [
            'properties' => [],
            'methods' => [],
            'hooks' => [],
            'enums' => [],
        ];
        $addedMethods = [];

        $leaveNode = function (Node $node) use (&$addedMethods, $create, $options, &$addedMembers) {
            if ($node instanceof Node\Stmt\UseUse) {
                $this->uses[] = (string) $node->name;
            }

            if ($node instanceof Node\Stmt\Use_ && $node->type === Node\Stmt\Use_::TYPE_FUNCTION) {
                foreach ($node->uses as $use) {
                    $this->functionUses[] = (string) $use->name;
                }
            }

            // Handle existing properties
            if ($node instanceof Node\Stmt\Property) {
                if ($options['methods'] ?? true) {
                    $methods = $this->createAccessorsIfNotExists(
                        (string) $node->props[0]->name,
                        $node,
                        $added
                    );

                    array_push(
                        $addedMembers['methods'],
                        ...$added
                    );

                    array_push(
                        $addedMethods,
                        ...$methods
                    );
                }
            }

            // After class traversed, add properties and methods
            if ($node instanceof Node\Stmt\Class_) {
                $last = static::getLastIndexOf($node->stmts, Node\Stmt\Property::class);

                if ($last === null) {
                    $last = static::getLastIndexOf($node->stmts, Node\Stmt\TraitUse::class);

                    if ($last !== null) {
                        $last++;
                    }
                }

                $last ??= 0;

                // Add methods for existing properties
                array_push(
                    $node->stmts,
                    ...$addedMethods
                );

                // Add new properties and methods
                foreach ($create as $i => $createColumn) {
                    if ($options['props'] ?? true) {
                        $prop = $this->createPropertyStatement($createColumn);

                        // Inject property by ordering.
                        array_splice(
                            $node->stmts,
                            $i + 1,
                            0,
                            [$prop]
                        );

                        $addedMembers['properties'][] = [(string) $prop->props[0]->name, $createColumn];
                    }

                    if (($options['methods'] ?? true) && isset($prop->props[0])) {
                        $methods = $this->createAccessorsIfNotExists(
                            (string) $prop->props[0]->name,
                            $prop,
                            $added
                        );

                        array_push(
                            $node->stmts,
                            ...$methods
                        );

                        array_push(
                            $addedMembers['methods'],
                            ...$added
                        );
                    }
                }

                // Loop all properties to create hooks
                $hasHooks = false;
                foreach ($node->stmts as $i => $stmt) {
                    if ($stmt instanceof Node\Stmt\Property) {
                        if ($options['hooks'] ?? true) {
                            $hooks = $this->createHooksIfNotExists((string) $stmt->props[0]->name, $stmt, $added);

                            $addedMembers['hooks'] = array_merge(
                                $addedMembers['hooks'],
                                $added
                            );
                        }

                        if ($stmt->hooks !== []) {
                            $hasHooks = true;
                        }
                    }
                }

                if ($hasHooks) {
                    $this->ignoreSniffer($node);
                }
                // End class node
            }

            if ($node instanceof Node\Stmt\Namespace_) {
                $last = static::getLastIndexOf($node->stmts, Node\Stmt\Use_::class) ?? 0;

                foreach ($this->addedUses as $use) {
                    array_splice(
                        $node->stmts,
                        ++$last,
                        0,
                        [
                            $this->createNodeFactory()
                                ->use($use)
                                ->getNode(),
                        ]
                    );
                }

                foreach ($this->addedFunctionUses as $use) {
                    array_splice(
                        $node->stmts,
                        ++$last,
                        0,
                        [
                            $this->createNodeFactory()
                                ->useFunction($use)
                                ->getNode(),
                        ]
                    );
                }
            }
        };

        $code = $this->convertCode(
            file_get_contents($ref->getFileName()),
            null,
            $leaveNode
        );

        $addedMembers['enums'] = $this->newEnums;

        return $code;
    }

    protected function ignoreSniffer(Node\Stmt\Class_ $node): void
    {
        $comments = $node->getComments();

        if (
            !array_any(
                $comments,
                static fn(Comment $comment) => str_contains($comment->getText(), 'phpcs:disable')
            )
        ) {
            $comments[] = new Comment('// phpcs:disable');
            $comments[] = new Comment('// todo: remove this when phpcs supports 8.4');
            $node->setAttribute('comments', $comments);
        }
    }

    /**
     * @param  Node\Stmt\Property  $propNode
     *
     * @return  array{ 0: ?Node\PropertyHook, 1: ?Node\PropertyHook }
     */
    protected function getHooks(Node\Stmt\Property $propNode): array
    {
        $hooks = [
            'get' => null,
            'set' => null,
        ];

        foreach ($propNode->hooks as $hook) {
            $hooks[(string) $hook->name] = $hook;
        }

        return array_values($hooks);
    }

    protected function getTypeAndDefaultFromDbColumn(DbColumn $dbColumn): array
    {
        $dt = $this->getORM()->getDb()->getPlatform()->getDataType();
        $default = $dbColumn->getColumnDefault();

        $dataType = $dbColumn->getDataType();
        $len = $dbColumn->getErratas()['custom_length'] ?? null;

        if ($dataType === 'datetime') {
            $type = '?Chronos';
            $default = null;

            $this->addUse(Chronos::class);
        } elseif ($dbColumn->columnName === 'state' && $dataType === 'tinyint' && enum_exists(BasicState::class)) {
            // Todo: This should move to unicorn package
            $type = 'BasicState';
            $default = Symbol::none();
            $this->addUse(BasicState::class);

            if ($dbColumn->getIsNullable()) {
                $type = '?' . $type;
                $default = null;
            }
        } elseif ($dataType === 'tinyint' && $len === '1') {
            $type = 'bool';
            $default = TypeCast::try($default, $type);
        } elseif ($dataType === 'binary' && $len === '16') {
            $type = '?UuidInterface';
            $default = null;
            $this->addUse(UuidInterface::class);
        } elseif ($enumName = $this->getMatchedEnum($dbColumn)) {
            $type = $enumName;
            $default = Symbol::none();

            if ($dbColumn->getIsNullable()) {
                $type = '?' . $type;
                $default = null;
            }
        } elseif ($this->isJsonType($dbColumn)) {
            $type = 'array';
            $default = [];
        } else {
            $type = $dt::getPhpType($dataType);

            if ($dbColumn->getIsNullable() && $default === 'NULL') {
                $default = null;
            } else {
                $default = TypeCast::try($default, $type);
            }

            if ($dbColumn->isAutoIncrement() || $dbColumn->getIsNullable()) {
                if (str_contains($type, '|')) {
                    $type .= '|null';
                } else {
                    $type = '?' . $type;
                }
            }
        }

        return [$type, $default];
    }

    protected function isJsonType(DbColumn $dbColumn): bool
    {
        $name = $dbColumn->getColumnName();

        return $this->once(
            'is.json:' . $name,
            function () use ($dbColumn, $name) {
                $tbManager = $this->getTableManager();

                if (str_contains($this->getORM()->getDb()->getDriver()->getVersion(), 'MariaDB')) {
                    $db = $this->getORM()->getDb();
                    $clause = (string) $db->select('CHECK_CLAUSE')
                        ->from('information_schema.CHECK_CONSTRAINTS')
                        ->where('CONSTRAINT_SCHEMA', $db->getDatabaseManager()->getName())
                        ->where('TABLE_NAME', $tbManager->getName())
                        ->where('CONSTRAINT_NAME', $name)
                        ->result();

                    return str_starts_with($clause, 'json_valid');
                }

                if ($tbManager->getPlatform()->getName() === 'MySQL') {
                    return strtolower($dbColumn->getDataType()) === 'json';
                }

                // todo: support other db

                return false;
            }
        );
    }

    /**
     * @return EntityMetadata
     */
    public function getMetadata(): EntityMetadata
    {
        return $this->metadata;
    }

    protected function isEnum(string $className): bool
    {
        return is_a($className, Enum::class, true)
            || is_a($className, \UnitEnum::class, true);
    }

    protected function getMatchedEnum(DbColumn $dbColumn): ?string
    {
        $comment = $dbColumn->getComment();

        // Find `enum:EnumClassName` without ::class
        if (!preg_match('/enum:(\w+)/', $comment, $matches)) {
            return null;
        }

        $enumName = $matches[1];

        $existsEnumClass = $this->findFQCN($enumName);

        if (!$existsEnumClass) {
            $ns = Str::removeRight($this->metadata->getReflector()->getNamespaceName(), 'Entity');
            $enumClass = $ns . 'Enum\\' . $enumName;
            $this->addUse($enumClass);
        } else {
            $this->newEnums[] = $existsEnumClass;
        }

        return $enumName;
    }

    public function addUse(string $ns): void
    {
        if (!in_array($ns, $this->uses, true)) {
            $this->uses[] = $ns;
            $this->addedUses[] = $ns;
        }
    }

    public function addFunctionUse(string $funcFullName): void
    {
        if (!in_array($funcFullName, $this->functionUses, true)) {
            $this->functionUses[] = $funcFullName;
            $this->addedFunctionUses[] = $funcFullName;
        }
    }

    /**
     * @return  array{ 0: string[], 1: string[], 2: string[] }
     */
    protected function getColumnsDiff(): array
    {
        $classColumns = array_map(
            fn(Column $column) => $column->getName(),
            $this->metadata->getColumns()
        );
        $dbColumns = $this->getTableColumns();

        $diffCreate = array_diff($dbColumns, $classColumns);
        $diffDelete = array_diff($classColumns, $dbColumns);
        $diffKeep = array_intersect($dbColumns, $classColumns);

        return [$diffCreate, $diffDelete, $diffKeep];
    }

    /**
     * @return  string[]
     */
    protected function getTableColumns(): array
    {
        return $this->getTableManager()->getColumnNames(true);
    }

    protected function getTableManager(): TableManager
    {
        return $this->getORM()->getDb()->getTableManager($this->metadata->getClassName());
    }

    /**
     * @param  string  $colName
     *
     * @return  Node\Stmt\Property
     */
    protected function createPropertyStatement(string $colName): Node\Stmt\Property
    {
        $factory = $this->createNodeFactory();

        $tbManager = $this->getTableManager();
        $dbColumn = $tbManager->getColumn($colName);

        if (!$dbColumn) {
            throw new \RuntimeException("Column: {$colName} not found.");
        }

        $propName = StrNormalize::toCamelCase($colName);

        [$type, $default] = $this->getTypeAndDefaultFromDbColumn($dbColumn);

        $propBuilder = $factory->property($propName)
            ->makePublic()
            ->setType($type);

        if (!Symbol::none()->is($default)) {
            $propBuilder->setDefault($default);
        }

        /** @var Node\Stmt\Property $prop */
        $prop = $propBuilder->getNode();

        $this->addUse(Column::class);

        $attrs = [
            $this->attribute(
                'Column',
                new Node\Scalar\String_($colName)
            ),
        ];

        if (in_array($dbColumn->getColumnName(), $this->getPks(), true)) {
            $this->addUse(PK::class);
            $attrs[] = $this->attribute('PK');
        }

        if ($dbColumn->isAutoIncrement()) {
            $this->addUse(AutoIncrement::class);
            $attrs[] = $this->attribute('AutoIncrement');
        }

        $prop->attrGroups[] = new Node\AttributeGroup($attrs);

        if ($type === 'bool') {
            $this->addUse(Cast::class);
            $prop->setAttribute('fullType', 'bool');
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'Cast',
                    new Node\Scalar\String_('bool'),
                    new Node\Scalar\String_('int'),
                ),
            );
        }

        // UUID binary(16)
        if ($type === '?UuidInterface' || $type === 'UuidInterface') {
            $this->addUse(CastNullable::class);
            $this->addUse(UUIDBin::class);
            $prop->setAttribute('fullType', UuidInterface::class);
            // $prop->attrGroups[] = $this->attributeGroup(
            //     $this->attribute(
            //         'CastNullable',
            //         extract: new Node\Scalar\String_('string'),
            //     ),
            // );

            $uuidDefault = 'UUID7';

            if ($dbColumn->getColumnDefault() !== null) {
                $uuidDefault = 'NIL';
            }

            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute('UUIDBin', $factory->classConstFetch('UUIDBin', $uuidDefault))
            );
        }

        if ($type === '?Chronos') {
            $this->addUse(CastNullable::class);
            $this->addUse(ServerTimeCast::class);
            $prop->setAttribute('fullType', Chronos::class);
            $prop->setAttribute('fullType', ServerTimeCast::class);
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'CastNullable',
                    new Node\Expr\ClassConstFetch(new Node\Name('ServerTimeCast'), 'class')
                ),
            );
        }

        if ($type === 'BasicState') {
            $this->addUse(Cast::class);
            $prop->setAttribute('fullType', BasicState::class);
            // $prop->attrGroups[] = $this->attributeGroup(
            //     $this->attribute(
            //         'Cast',
            //         extract: new Node\Scalar\String_('int')
            //     ),
            // );
            // $prop->attrGroups[] = $this->attributeGroup(
            //     $this->attribute(
            //         'Cast',
            //         new Node\Expr\ClassConstFetch(new Node\Name('BasicState'), 'class')
            //     ),
            // );
        }

        if ($enumName = $this->getMatchedEnum($dbColumn)) {
            $enumName = Str::removeLeft($enumName, '?');
            $prop->setAttribute('fullType', $this->findFQCN($enumName));

            // if ($dbColumn->getIsNullable()) {
            //     $this->addUse(CastNullable::class);
            //     $prop->attrGroups[] = $this->attributeGroup(
            //         $this->attribute(
            //             'CastNullable',
            //             extract: new Node\Scalar\String_('string'),
            //         ),
            //     );
            // } else {
            //     $this->addUse(Cast::class);
            //     $prop->attrGroups[] = $this->attributeGroup(
            //         $this->attribute(
            //             'Cast',
            //             extract: new Node\Scalar\String_('string'),
            //         ),
            //     );
            // }
        }

        if ($this->isJsonType($dbColumn)) {
            $this->addUse(Cast::class);
            $this->addUse(JsonObject::class);

            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute('JsonObject')
            );
        }

        $event = $this->emit(
            new BuildEntityPropertyEvent(
                propName: $propName,
                prop: $prop,
                column: $dbColumn,
                entityMemberBuilder: $this
            )
        );

        return $event->prop;
    }

    protected function getPks(): array
    {
        return $this->once(
            'pks',
            function () {
                $constraints = $this->getTableManager()->getConstraints();
                /** @var ?Constraint $constraint */
                $constraint = Arr::findFirst(
                    $constraints,
                    fn(Constraint $constraint) => $constraint->constraintType === Constraint::TYPE_PRIMARY_KEY
                );

                if (!$constraint) {
                    return [];
                }

                return array_keys($constraint->getColumns());
            }
        );
    }

    public function findFQCN(string $shortName): ?string
    {
        foreach ($this->uses as $use) {
            if (str_ends_with(strtolower($use), strtolower($shortName))) {
                return $use;
            }
        }

        return null;
    }
}
