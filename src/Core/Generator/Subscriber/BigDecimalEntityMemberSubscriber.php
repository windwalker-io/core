<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Subscriber;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use PhpParser\Node;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Database\Schema\Ddl\Column;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\ORM\Attributes\Cast;
use Windwalker\ORM\Attributes\CastNullable;

/**
 * Generates BigDecimal properties for `decimal` columns when building entities.
 */
#[EventSubscriber]
class BigDecimalEntityMemberSubscriber
{
    /**
     * @param  int           $defaultScale         Scale used when the DB column does not report one.
     * @param  RoundingMode  $defaultRoundingMode  Rounding mode passed to \Windwalker\try_big_decimal().
     */
    public function __construct(
        private int $defaultScale = 4,
        private RoundingMode $defaultRoundingMode = RoundingMode::HalfUp,
    ) {}

    #[ListenTo(BuildEntityPropertyEvent::class)]
    public function buildProperty(BuildEntityPropertyEvent $event): void
    {
        if (!self::isDecimalColumn($event->column)) {
            return;
        }

        $builder = $event->entityMemberBuilder;
        $nullable = $event->column->getIsNullable();
        $prop = $event->prop;

        $builder->addUse(BigDecimal::class);

        $typeNode = new Node\Name('BigDecimal');
        $prop->type = $nullable ? new Node\NullableType($typeNode) : $typeNode;
        $prop->props[0]->default = $nullable ? new Node\Expr\ConstFetch(new Node\Name('null')) : null;

        if ($nullable) {
            $builder->addUse(CastNullable::class);
            $attr = $builder->attribute(
                'CastNullable',
                new Node\Expr\ClassConstFetch(new Node\Name('BigDecimal'), 'class')
            );
        } else {
            $builder->addUse(Cast::class);
            $attr = $builder->attribute(
                'Cast',
                new Node\Expr\ClassConstFetch(new Node\Name('BigDecimal'), 'class')
            );
        }

        $prop->attrGroups[] = $builder->attributeGroup($attr);
        $prop->setAttribute('fullType', BigDecimal::class);
    }

    #[ListenTo(BuildEntityHookEvent::class)]
    public function buildSetHook(BuildEntityHookEvent $event): void
    {
        if (!$event->isSet || $event->hook || !self::isDecimalColumn($event->column)) {
            return;
        }

        $builder = $event->entityMemberBuilder;
        $scale = $event->column->getNumericScale() ?? $this->defaultScale;

        $builder->addUse(BigDecimal::class);

        $types = [
            new Node\Name('BigDecimal'),
            new Node\Identifier('string'),
            new Node\Identifier('float'),
            new Node\Identifier('int'),
            new Node\Identifier('null'),
        ];

        $value = new Node\Expr\Variable('value');
        $convert = new Node\Expr\FuncCall(
            new Node\Name\FullyQualified('Windwalker\try_big_decimal'),
            [
                new Node\Arg($value),
                new Node\Arg(new Node\Scalar\LNumber($scale)),
                new Node\Arg(
                    new Node\Expr\ClassConstFetch(
                        new Node\Name\FullyQualified(RoundingMode::class),
                        $this->defaultRoundingMode->name
                    )
                ),
            ]
        );

        $assign = new Node\Expr\Assign(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $event->propName),
            $convert
        );

        $event->hook = $builder->createHookNode(new Node\UnionType($types), $assign);
    }

    private static function isDecimalColumn(?Column $column): bool
    {
        if ($column === null || $column->getDataType() !== 'decimal') {
            return false;
        }

        if (!class_exists(BigDecimal::class)) {
            throw new \RuntimeException(
                'Please install `brick/math` to support decimal columns as BigDecimal.'
            );
        }

        return true;
    }
}
