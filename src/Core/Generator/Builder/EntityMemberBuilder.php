<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Node;
use ReflectionAttribute;
use ReflectionProperty;
use Unicorn\Enum\BasicState;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Generator\Event\BuildEntityMethodEvent;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column as DbColumn;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\Cast;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Cast\JsonCast;
use Windwalker\ORM\Metadata\EntityMetadata;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\Symbol;
use Windwalker\Utilities\TypeCast;

/**
 * The EntityMemberBuilder class.
 */
class EntityMemberBuilder extends AbstractAstBuilder implements EventAwareInterface
{
    use CoreEventAwareTrait;
    use InstanceCacheTrait;

    protected array $uses = [];

    protected array $addedUses = [];

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
        [$create, $delete, $keep] = $this->getColumnsDiff($class);

        $addedMembers = [
            'properties' => [],
            'methods' => [],
        ];
        $addedMethods = [];

        $leaveNode = function (Node $node) use (&$addedMethods, $create, $options, &$addedMembers) {
            if ($node instanceof Node\Stmt\UseUse) {
                $this->uses[] = (string) $node->name;
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
                $last = $this->getLastOf($node->stmts, Node\Stmt\Property::class) ?? null;

                if ($last === null) {
                    $last = $this->getLastOf($node->stmts, Node\Stmt\TraitUse::class) ?? null;

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

                        array_splice(
                            $node->stmts,
                            $i + $last,
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
            }

            if ($node instanceof Node\Stmt\Namespace_) {
                $last = $this->getLastOf($node->stmts, Node\Stmt\Use_::class) ?? 0;

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
            }
        };

        return $this->convertCode(
            file_get_contents($ref->getFileName()),
            null,
            $leaveNode
        );
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
        } elseif ($dbColumn->columnName === 'state' && $dataType === 'tinyint') {
            $type = 'BasicState';
            $default = Symbol::none();
            $this->addUse(BasicState::class);
        } elseif ($dataType === 'tinyint' && $len === '1') {
            $type = 'bool';
            $default = TypeCast::try($default, $type);
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
                        ->where('CONSTRAINT_SCHEMA', $db->getDatabase()->getName())
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

    protected function createAccessorsIfNotExists(
        string $propName,
        Node\Stmt\Property $prop,
        ?array &$added = null
    ): array {
        $added = [];
        $factory = $this->createNodeFactory();
        $ref = $this->metadata->getReflector();
        $type = $prop->type;
        $tbManager = $this->getTableManager();

        $ref = $this->metadata->getReflector();
        $propRef = $ref->getProperty($propName);
        $col = AttributesAccessor::getFirstAttributeInstance(
            $propRef,
            Column::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        $colName = $col?->getName();
        $column = $colName ? $tbManager->getColumn($colName) : null;

        $isBool = false;
        $specialSetter = null;
        $typeNode = $type;

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
        }

        if ($typeNode instanceof Node\UnionType) {
            $isBool = in_array('bool', $typeNode->types, true);
        } elseif ($typeNode instanceof Node\Identifier) {
            $isBool = $typeNode->name === 'bool';
        } elseif ($typeNode instanceof Node\Name) {
            $specialSetter = 'build' . $typeNode . 'Setter';
        }

        if (str_starts_with($propName, 'is')) {
            $getter = $propName;
            $setter = 'set' . ucfirst($propName);
        } else {
            $setter = 'set' . ucfirst($propName);

            if ($isBool) {
                $getter = 'is' . ucfirst($propName);
            } else {
                $getter = 'get' . ucfirst($propName);
            }
        }

        $methods = [];

        // Getter
        if (!$ref->hasMethod($getter)) {
            $added[] = $getter;
            $method = $factory->method($getter)
                ->makePublic()
                ->addStmt(
                    new Node\Stmt\Return_(
                        $factory->propertyFetch(
                            new Node\Expr\Variable('this'),
                            $propName
                        )
                    )
                )
                ->setReturnType($type)
                ->getNode();

            $event = $this->emit(
                BuildEntityMethodEvent::class,
                [
                    'accessorType' => 'getter',
                    'methodName' => $getter,
                    'method' => $method,
                    'propName' => $propName,
                    'column' => $column,
                    'prop' => $prop,
                    'type' => $type,
                    'entityMemberBuilder' => $this,
                ]
            );

            $methods[] = $event->getMethod();
        }

        if (!$ref->hasMethod($setter)) {
            $added[] = $setter;

            if ($specialSetter && method_exists($this, $specialSetter)) {
                $method = $this->$specialSetter($setter, $propName, $type);
            } else {
                $method = $factory->method($setter)
                    ->makePublic()
                    ->addParam(
                        $factory->param($propName)
                            ->setType($type)
                    )
                    ->addStmt(
                        new Node\Expr\Assign(
                            $factory->propertyFetch(
                                new Node\Expr\Variable('this'),
                                $propName
                            ),
                            new Node\Expr\Variable($propName),
                        )
                    )
                    ->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('this')))
                    ->setReturnType('static')
                    ->getNode();
            }

            $event = $this->emit(
                BuildEntityMethodEvent::class,
                [
                    'accessorType' => 'setter',
                    'methodName' => $getter,
                    'method' => $method,
                    'propName' => $propName,
                    'column' => $column,
                    'prop' => $prop,
                    'type' => $type,
                    'entityMemberBuilder' => $this,
                ]
            );

            $methods[] = $event->getMethod();
        }

        return $methods;
    }

    /**
     * @return EntityMetadata
     */
    public function getMetadata(): EntityMetadata
    {
        return $this->metadata;
    }

    protected function buildChronosSetter(string $setter, string $propName, Node $type): Node
    {
        $factory = $this->createNodeFactory();

        return $factory->method($setter)
            ->makePublic()
            ->addParam(
                $factory->param($propName)
                    ->setType('\DateTimeInterface|string|null')
            )
            ->addStmt(
                new Node\Expr\Assign(
                    $factory->propertyFetch(
                        new Node\Expr\Variable('this'),
                        $propName
                    ),
                    $factory->staticCall(
                        new Node\Name('Chronos'),
                        'wrapOrNull',
                        [
                            new Node\Expr\Variable($propName),
                        ]
                    ),
                )
            )
            ->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('this')))
            ->setReturnType('static')
            ->getNode();
    }

    public function getLastOf(array $stmts, string $class): ?int
    {
        foreach ($stmts as $i => $stmt) {
            if ($stmt instanceof $class) {
                return $i;
            }
        }

        return null;
    }

    public function addUse(string $ns): void
    {
        if (!in_array($ns, $this->uses, true)) {
            $this->uses[] = $ns;
            $this->addedUses[] = $ns;
        }
    }

    /**
     * getColumnsDiff
     *
     * @param  string  $table
     *
     * @return  array<array>
     */
    protected function getColumnsDiff(string $table): array
    {
        $classColumns = array_map(
            fn(Column $column) => $column->getName(),
            $this->metadata->getColumns()
        );
        $dbColumns = $this->getTableColumns($table);

        $diffCreate = array_diff($dbColumns, $classColumns);
        $diffDelete = array_diff($classColumns, $dbColumns);
        $diffKeep = array_intersect($dbColumns, $classColumns);

        return [$diffCreate, $diffDelete, $diffKeep];
    }

    protected function getTableColumns(string $table): array
    {
        return $this->getTableManager()->getColumnNames(true);
    }

    protected function getTableManager(): TableManager
    {
        return $this->getORM()->getDb()->getTable($this->metadata->getClassName());
    }

    /**
     * createPropertyStatement
     *
     * @param  mixed  $createColumn
     *
     * @return  Node\Stmt\Property
     */
    protected function createPropertyStatement(mixed $createColumn): Node\Stmt\Property
    {
        $factory = $this->createNodeFactory();

        $tbManager = $this->getTableManager();
        $dbColumn = $tbManager->getColumn($createColumn);
        $propName = StrNormalize::toCamelCase($createColumn);

        [$type, $default] = $this->getTypeAndDefaultFromDbColumn($dbColumn);

        $propBuilder = $factory->property($propName)
            ->makeProtected()
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
                new Node\Scalar\String_($createColumn)
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

        if ($type === '?Chronos') {
            $this->addUse(CastNullable::class);
            $prop->setAttribute('fullType', Chronos::class);
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'CastNullable',
                    new Node\Expr\ClassConstFetch(new Node\Name('Chronos'), 'class')
                ),
            );
        }

        if ($type === 'BasicState') {
            $this->addUse(Cast::class);
            $prop->setAttribute('fullType', BasicState::class);
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'Cast',
                    new Node\Scalar\String_('int')
                ),
            );
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'Cast',
                    new Node\Expr\ClassConstFetch(new Node\Name('BasicState'), 'class')
                ),
            );
        }

        if ($this->isJsonType($dbColumn)) {
            $this->addUse(Cast::class);
            $this->addUse(JsonCast::class);

            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'Cast',
                    new Node\Expr\ClassConstFetch(new Node\Name('JsonCast'), 'class')
                ),
            );
        }

        $event = $this->emit(
            BuildEntityPropertyEvent::class,
            [
                'prop' => $prop,
                'propName' => $propName,
                'column' => $dbColumn,
                'entityMemberBuilder' => $this,
            ]
        );

        return $event->getProp();
    }

    protected function getPks(): array
    {
        return $this->once(
            'pks',
            function () {
                $constraints = $this->getTableManager()->getConstraints();
                /** @var Constraint $constraint */
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
