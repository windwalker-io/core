<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Subscriber;

use Brick\Math\BigInteger;
use PhpParser\Node;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\ORM\Attributes\Cast;
use Windwalker\ORM\Attributes\CastNullable;

/**
 * Generates BigInteger properties for `bigint` columns when building entities.
 */
#[EventSubscriber]
class BigIntegerEntityMemberSubscriber
{
    #[ListenTo(BuildEntityPropertyEvent::class)]
    public function buildProperty(BuildEntityPropertyEvent $event): void
    {
        if (!self::isBigIntColumn($event->column)) {
            return;
        }

        $builder = $event->entityMemberBuilder;
        $nullable = $event->column->getIsNullable();
        $prop = $event->prop;

        $builder->addUse(BigInteger::class);

        $typeNode = new Node\Name('BigInteger');
        $prop->type = $nullable ? new Node\NullableType($typeNode) : $typeNode;
        $prop->props[0]->default = $nullable ? new Node\Expr\ConstFetch(new Node\Name('null')) : null;

        if ($nullable) {
            $builder->addUse(CastNullable::class);
            $attr = $builder->attribute(
                'CastNullable',
                new Node\Expr\ClassConstFetch(new Node\Name('BigInteger'), 'class')
            );
        } else {
            $builder->addUse(Cast::class);
            $attr = $builder->attribute(
                'Cast',
                new Node\Expr\ClassConstFetch(new Node\Name('BigInteger'), 'class')
            );
        }

        $prop->attrGroups[] = $builder->attributeGroup($attr);
        $prop->setAttribute('fullType', BigInteger::class);
    }

    #[ListenTo(BuildEntityHookEvent::class)]
    public function buildSetHook(BuildEntityHookEvent $event): void
    {
        if (!$event->isSet || $event->hook || !self::isBigIntColumn($event->column)) {
            return;
        }

        $builder = $event->entityMemberBuilder;

        $builder->addUse(BigInteger::class);

        // The hook must accept null so it stays compatible with nullable properties.
        $types = [
            new Node\Name('BigInteger'),
            new Node\Identifier('string'),
            new Node\Identifier('float'),
            new Node\Identifier('int'),
            new Node\Identifier('null'),
        ];

        $value = new Node\Expr\Variable('value');
        $convert = new Node\Expr\FuncCall(
            new Node\Name\FullyQualified('Windwalker\try_big_integer'),
            [new Node\Arg($value)]
        );

        $assign = new Node\Expr\Assign(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $event->propName),
            $convert
        );

        $event->hook = $builder->createHookNode(new Node\UnionType($types), $assign);
    }

    private static function isBigIntColumn(?Column $column): bool
    {
        if ($column === null || $column->getDataType() !== 'bigint') {
            return false;
        }

        self::requireBrickMath();

        return true;
    }

    private static function requireBrickMath(): void
    {
        if (!class_exists(BigInteger::class)) {
            throw new \RuntimeException(
                'Please install `brick/math` to support BigInteger columns.'
            );
        }
    }
}
