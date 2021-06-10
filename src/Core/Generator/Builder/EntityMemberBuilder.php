<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Node;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Column as DbColumn;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Metadata\EntityMetadata;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\TypeCast;

/**
 * The EntityMemberBuilder class.
 */
class EntityMemberBuilder extends AbstractAstBuilder
{
    use InstanceCacheTrait;

    protected ?Node\Stmt\Namespace_ $nsStmt = null;

    protected array $uses = [];

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
        $ref   = $this->metadata->getReflector();
        $class = $this->metadata->getClassName();
        $props = $this->metadata->getProperties();
        /** @var \ReflectionProperty $lastProp */
        $lastProp = $props[array_key_last($props)];
        [$create, $delete, $keep] = $this->getColumnsDiff($class);

        $addedMembers = [
            'properties' => [],
            'methods' => [],
        ];
        $addedMethods = [];

        $enterNode = function (Node $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $this->nsStmt = $node;
            }
            if ($node instanceof Node\Stmt\UseUse) {
                $this->uses[] = (string) $node->name;
            }
        };

        $leaveNode = function (Node $node) use (&$addedMethods, $create, $options, &$addedMembers) {
            // Handle existing properties
            if ($node instanceof Node\Stmt\Property) {
                if ($options['methods'] ?? true) {
                    $methods = $this->createAccessorsIfNotExists(
                        (string) $node->props[0]->name,
                        $node->type,
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
                $last = $this->getLastOf($node->stmts, Node\Stmt\Property::class);

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
                            $i + $last + 1,
                            0,
                            [$prop]
                        );

                        $addedMembers['properties'][] = [(string) $prop->props[0]->name, $createColumn];
                    }

                    if ($options['methods'] ?? true) {
                        $methods = $this->createAccessorsIfNotExists(
                            (string) $prop->props[0]->name,
                            $prop->type,
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
        };

        return $this->convertCode(
            file_get_contents($ref->getFileName()),
            $enterNode,
            $leaveNode
        );
    }

    protected function getTypeAndDefaultFromDbColumn(DbColumn $dbColumn): array
    {
        $dt      = $this->getORM()->getDb()->getPlatform()->getDataType();
        $default = $dbColumn->getColumnDefault();

        switch ($dbColumn->getDataType()) {
            case 'datetime':
                $type    = '?Chronos';
                $default = null;

                $this->addUse(Chronos::class);
                break;

            default:
                $type    = $dt::getPhpType($dbColumn->getDataType());
                $default = TypeCast::try($default, $type);

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

    protected function createAccessorsIfNotExists(
        string $propName,
        Node $type,
        array &$added = null
    ): array {
        $added   = [];
        $factory = $this->createNodeFactory();
        $ref     = $this->metadata->getReflector();

        $isBool   = false;
        $typeNode = $type;

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
        }

        if ($typeNode instanceof Node\UnionType) {
            $isBool = in_array('bool', $typeNode->types, true);
        } elseif ($typeNode instanceof Node\Identifier) {
            $isBool = $typeNode->name === 'bool';
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
            $added[]   = $getter;
            $methods[] = $factory->method($getter)
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
        }

        if (!$ref->hasMethod($setter)) {
            $added[]   = $setter;
            $methods[] = $factory->method($setter)
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

        return $methods;
    }

    public function getLastOf(array $stmts, string $class): int
    {
        $stmts = array_filter(
            array_map(fn($stmt) => $stmt::class, $stmts),
            fn($stmt) => $stmt === $class
        );

        return array_key_last($stmts);
    }

    protected function addUse(string $ns): void
    {
        if (!in_array($ns, $this->uses, true)) {
            $this->uses[]          = $ns;
            $this->nsStmt->stmts[] = $this->createNodeFactory()
                ->use($ns)
                ->getNode();
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
        $dbColumns    = $this->getTableColumns($table);

        $diffCreate = array_diff($dbColumns, $classColumns);
        $diffDelete = array_diff($classColumns, $dbColumns);
        $diffKeep   = array_intersect($dbColumns, $classColumns);

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
        $dbColumn  = $tbManager->getColumn($createColumn);
        $propName  = StrNormalize::toCamelCase($createColumn);

        [$type, $default] = $this->getTypeAndDefaultFromDbColumn($dbColumn);

        /** @var Node\Stmt\Property $prop */
        $prop = $factory->property($propName)
            ->makeProtected()
            ->setType($type)
            ->setDefault($default)
            ->getNode();

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

        if ($type === '?Chronos') {
            $this->addUse(CastNullable::class);
            $prop->attrGroups[] = $this->attributeGroup(
                $this->attribute(
                    'CastNullable',
                    new Node\Expr\ClassConstFetch(new Node\Name('Chronos'), 'class')
                ),
            );
        }

        return $prop;
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
}
