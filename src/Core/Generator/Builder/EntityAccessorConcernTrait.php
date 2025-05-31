<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Node;
use ReflectionAttribute;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Generator\Event\BuildEntityMethodEvent;
use Windwalker\ORM\Attributes\Column;
use Windwalker\Utilities\Enum\EnumMetaInterface;

trait EntityAccessorConcernTrait
{
    /**
     * @deprecated  Use property hooks instead.
     */
    protected function createAccessorsIfNotExists(
        string $propName,
        Node\Stmt\Property $propNode,
        ?array &$added = null
    ): array {
        $added = [];
        $factory = $this->createNodeFactory();
        $type = $propNode->type;
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
                new BuildEntityMethodEvent(
                    accessorType: 'getter',
                    methodName: $getter,
                    propName: $propName,
                    prop: $propNode,
                    column: $column,
                    method: $method,
                    type: $type,
                    entityMemberBuilder: $this,
                )
            );

            $methods[] = $event->method;
        }

        // Setter
        if (!$ref->hasMethod($setter)) {
            $added[] = $setter;

            if ($specialSetter && method_exists($this, $specialSetter)) {
                $method = $this->$specialSetter($setter, $propName, $type, $column);
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
                new BuildEntityMethodEvent(
                    accessorType: 'setter',
                    methodName: $setter,
                    propName: $propName,
                    prop: $propNode,
                    column: $column,
                    method: $method,
                    type: $type,
                    entityMemberBuilder: $this,
                )
            );

            if (!$typeNode instanceof Node\UnionType) {
                $className = $this->findFQCN((string) $typeNode);

                // Enum accept pure value
                if ($className && class_exists($className) && $this->isEnum($className)) {
                    if ($column) {
                        $subType = $column->isNumeric() ? 'int' : 'string';
                    } else {
                        $subType = 'int|string';
                    }

                    $subType .= '|' . $type;

                    $method->params[0] = $factory->param($propName)
                        ->setType($subType)
                        ->getNode();

                    if (is_a($className, EnumMetaInterface::class, true)) {
                        $enum = $factory->staticCall(
                            new Node\Name($type),
                            'wrap',
                            [
                                new Node\Expr\Variable($propName),
                            ]
                        );
                    } else {
                        $enum = $factory->new(
                            new Node\Name($type),
                            [
                                new Node\Expr\Variable($propName),
                            ]
                        );
                    }

                    $method->stmts[0] = new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $factory->propertyFetch(
                                new Node\Expr\Variable('this'),
                                $propName
                            ),
                            $enum
                        )
                    );
                }
            }

            $methods[] = $event->method;
        }

        return $methods;
    }

    /**
     * @deprecated  Use property hooks instead.
     */
    protected function buildUuidInterfaceSetter(string $setter, string $propName, Node $type): Node
    {
        $factory = $this->createNodeFactory();

        return $factory->method($setter)
            ->makePublic()
            ->addParam(
                $factory->param($propName)
                    ->setType('UuidInterface|string|null')
            )
            ->addStmt(
                new Node\Expr\Assign(
                    $factory->propertyFetch(
                        new Node\Expr\Variable('this'),
                        $propName
                    ),
                    $factory->staticCall(
                        new Node\Name('UUIDBin'),
                        'tryWrap',
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

    /**
     * @deprecated  Use property hooks instead.
     */
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
                        'tryWrap',
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
}
