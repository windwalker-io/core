<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Builder;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Param;
use Ramsey\Uuid\UuidInterface;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
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

        if ($typeNode instanceof Node\NullableType) {
            $typeNode = $typeNode->type;
        }

        if ($typeNode instanceof Node\Name) {
            $specialSetHook = 'build' . $typeNode . 'SetHook';
        }

        if (!$getHook) {
            // Getter hook is not necessary by default.

            // @event
            $event = $this->emit(
                new BuildEntityHookEvent(
                    hookType: \PropertyHookType::Get,
                    propName: $propName,
                    prop: $propNode,
                    column: $column,
                    type: $type,
                    entityMemberBuilder: $this,
                )
            );

            if ($event->hook) {
                $getHook = $event->hook;
                $added[$propName][] = 'hook(get)';
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

            if (!$setHook && !$typeNode instanceof Node\UnionType) {
                $className = $this->findFQCN((string) $typeNode);

                // Enum accept scalar value
                /** @var class-string<\UnitEnum> $className */
                if ($className && class_exists($className) && $this->isEnum($className)) {
                    $setHook = $this->createHookAssignValue(
                        $propName,
                        $type,
                        new Node\Expr\Variable('value')
                    );

                    $enumRef = new \ReflectionEnum($className);

                    if ($enumRef->isBacked()) {
                        $subType = $enumRef->getBackingType()?->getName() ?? 'int';
                    } else {
                        $subType = 'int|string';
                    }

                    if ($type instanceof Node\NullableType) {
                        $enumClass = $type->type;
                        $subType .= '|' . $enumClass . '|null';
                        $nullable = true;
                    } else {
                        $enumClass = $type;
                        $subType .= '|' . $type;
                        $nullable = false;
                    }

                    $setHook->params[0] = $factory->param('value')
                        ->setType($subType)
                        ->getNode();

                    if (is_a($className, EnumMetaInterface::class, true)) {
                        $enum = $factory->staticCall(
                            new Node\Name($enumClass),
                            $nullable ? 'tryWrap' : 'wrap',
                            [
                                new Node\Expr\Variable('value'),
                            ]
                        );
                    } else {
                        $enum = $factory->staticCall(
                            new Node\Name($enumClass),
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
