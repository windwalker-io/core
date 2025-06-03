<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Node;
use PhpParser\Node\Param;
use Ramsey\Uuid\UuidInterface;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
use Windwalker\Data\Collection;
use Windwalker\Data\ValueObject;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Utilities\Enum\EnumMetaInterface;

trait EntityHooksConcernTrait
{
    protected function createHooksIfNotExists(
        string $propName,
        Node\Stmt\Property $propNode,
        ?array &$added = null
    ): array {
        $added = [];

        $tbManager = $this->getTableManager();
        $factory = $this->createNodeFactory();
        [$getHook, $setHook] = $this->getHooks($propNode);

        $col = $this->metadata->getColumnByPropertyName($propName);

        $colName = $col?->getName();
        $column = $colName ? $tbManager->getColumn($colName) : null;

        $type = $propNode->type;
        $isBool = false;
        $specialSetHook = null;
        $typeNode = $type;
        $isNullable = false;

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
            $isNullable = true;
        }

        if ($typeNode instanceof Node\Name) {
            $specialSetHook = 'build' . $typeNode . 'SetHook';
        }

        $className = $this->findFQCN((string) $typeNode);

        if (!$getHook) {
            if ($className) {
                // Getter hook is not necessary by default.
                // ValueObject or Collection
                if (
                    !$isNullable
                    && (
                        is_a($className, Collection::class, true)
                        || is_a($className, ValueObject::class, true)
                    )
                ) {
                    $getHook = new Node\PropertyHook(
                        'get',
                        [
                            new Node\Stmt\Return_(
                                new Node\Expr\AssignOp\Coalesce(
                                    $factory->propertyFetch(
                                        new Node\Expr\Variable('this'),
                                        $propName,
                                    ),
                                    $factory->new($typeNode)
                                )
                            )
                        ]
                    );
                }
            }

            // @event
            $event = $this->emit(
                new BuildEntityHookEvent(
                    hookType: \PropertyHookType::Get,
                    propName: $propName,
                    prop: $propNode,
                    column: $column,
                    type: $type,
                    entityMemberBuilder: $this,
                    hook: $getHook,
                )
            );

            if ($event->hook) {
                $getHook = $event->hook;
                $added[$propName][] = 'get';
            }
        }

        if (!$setHook) {
            if ($specialSetHook && method_exists($this, $specialSetHook)) {
                $setHook = $this->{$specialSetHook}($propName, $propNode, $column);
            }
            // else {
            //     $setHook = $this->createHookAssignValue(
            //         $propName,
            //         $type,
            //         new Node\Expr\Variable('value')
            //     );
            // }

            if (!$setHook && !$typeNode instanceof Node\UnionType && $className) {
                // ValueObject or Collection
                if (
                    is_a($className, Collection::class, true)
                    || is_a($className, ValueObject::class, true)
                ) {
                    /** @var class-string<Collection> $className */
                    $typeClass = (string) $typeNode;
                    $typeString = $typeNode . '|array';

                    if ($isNullable) {
                        $typeString .= '|null';
                    }

                    $setHook = $this->createHookAssignValue(
                        $propName,
                        new Node\Identifier($typeString),
                        $factory->staticCall(
                            new Node\Name($typeClass),
                            $isNullable ? 'tryWrap' : 'wrap',
                            [
                                new Node\Expr\Variable('value'),
                            ]
                        )
                    );
                }

                // Enum accept scalar value
                if ($this->isEnum($className)) {
                    /** @var class-string<\UnitEnum> $className */
                    $setHook = $this->createHookAssignValue(
                        $propName,
                        $type,
                        new Node\Expr\Variable('value')
                    );

                    $enumRef = new \ReflectionEnum($className);

                    $typeClass = (string) $typeNode;
                    $typeString = (string) $typeNode;

                    if ($enumRef->isBacked()) {
                        $typeString .= '|' . ($enumRef->getBackingType()?->getName() ?? 'int');
                    } else {
                        $typeString .= '|int|string';
                    }

                    if ($isNullable) {
                        $typeString .= '|null';
                    }

                    $setHook->params[0] = $factory->param('value')
                        ->setType($typeString)
                        ->getNode();

                    if (is_a($className, EnumMetaInterface::class, true)) {
                        $enum = $factory->staticCall(
                            new Node\Name($typeClass),
                            $isNullable ? 'tryWrap' : 'wrap',
                            [
                                new Node\Expr\Variable('value'),
                            ]
                        );
                    } else {
                        $enum = $factory->staticCall(
                            new Node\Name($typeClass),
                            'from',
                            [
                                new Node\Expr\Variable('value'),
                            ]
                        );
                    }

                    $setHook->body[0] = new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            $factory->propertyFetch(
                                new Node\Expr\Variable('this'),
                                $propName
                            ),
                            $enum
                        )
                    );

                    $type = $setHook->params[0]->type;
                }
            }

            // @event
            $event = $this->emit(
                new BuildEntityHookEvent(
                    hookType: \PropertyHookType::Set,
                    propName: $propName,
                    prop: $propNode,
                    column: $column,
                    type: $type,
                    entityMemberBuilder: $this,
                    hook: $setHook,
                )
            );

            if ($event->hook) {
                $setHook = $event->hook;

                $added[$propName][] = 'set';
            }
        }

        $propNode->hooks = array_filter([$getHook, $setHook]);

        return $propNode->hooks;
    }

    protected function nonUnionToTypeName(
        string|Node\ComplexType|Node\Name $type,
        ?string &$className = null,
        ?bool &$nullable = null,
    ): string {
        if ($type instanceof Node\NullableType) {
            $className = (string) $type->type;
            $typeName = $className . '|null';
            $nullable = true;
        } else {
            $className = (string) $type;
            $typeName = (string) $type;
            $nullable = false;
        }

        return $typeName;
    }

    protected function createHookAssignValue(
        string $propName,
        Node\Name|Node\Identifier|Node\ComplexType|Node|null $type,
        Node\Expr $expr,
    ): Node\PropertyHook {
        $factory = $this->createNodeFactory();

        return $this->createHookNode(
            $type,
            [
                new Node\Expr\Assign(
                    $factory->propertyFetch(
                        new Node\Expr\Variable('this'),
                        $propName
                    ),
                    $expr,
                ),
            ]
        );
    }

    public function createHookNode(
        Node\Name|Node\Identifier|Node\ComplexType|Node|null $type,
        array $expressions = []
    ): Node\PropertyHook {
        $expressions = array_map(
            fn($expr) => new Node\Stmt\Expression($expr),
            $expressions
        );

        return new Node\PropertyHook(
            'set',
            $expressions,
            [
                'params' => [
                    new Param(
                        var: new Node\Expr\Variable('value'),
                        type: $type,
                    ),
                ],
            ]
        );
    }

    protected function buildUuidInterfaceSetHook(
        string $propName,
        Node\Stmt\Property $propNode,
        Column $column
    ): Node\PropertyHook {
        $factory = $this->createNodeFactory();

        $this->addUse(UuidInterface::class);

        return $this->createHookAssignValue(
            $propName,
            new Node\Identifier('UuidInterface|string|null'),
            $factory->staticCall(
                new Node\Name('UUIDBin'),
                'tryWrap',
                [
                    new Node\Expr\Variable('value'),
                ]
            )
        );
    }

    protected function buildChronosSetHook(
        string $propName,
        Node\Stmt\Property $propNode,
        Column $column
    ): Node\PropertyHook {
        $factory = $this->createNodeFactory();

        $this->addUse(Chronos::class);

        return $this->createHookAssignValue(
            $propName,
            new Node\Identifier('\DateTimeInterface|string|null'),
            $factory->staticCall(
                new Node\Name('Chronos'),
                'tryWrap',
                [
                    new Node\Expr\Variable('value'),
                ]
            )
        );
    }
}
