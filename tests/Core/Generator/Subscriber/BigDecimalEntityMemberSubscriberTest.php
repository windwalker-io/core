<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Generator\Subscriber;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Core\Generator\Subscriber\BigDecimalEntityMemberSubscriber;
use Windwalker\Database\Schema\Ddl\Column as DbColumn;
use Windwalker\ORM\Metadata\EntityMetadata;

/**
 * Covers BigDecimalEntityMemberSubscriber: property type and set hook generation for decimal columns.
 */
class BigDecimalEntityMemberSubscriberTest extends TestCase
{
    private BigDecimalEntityMemberSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new BigDecimalEntityMemberSubscriber();
    }

    private function makeBuilder(): EntityMemberBuilder
    {
        return new EntityMemberBuilder($this->createStub(EntityMetadata::class));
    }

    #[Test]
    public function buildPropertyCreatesNonNullableBigDecimal(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('amount')]);
        $column = new DbColumn('amount', 'decimal(12,4)', false);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('amount', $prop, $column, $this->makeBuilder())
        );

        self::assertSame('BigDecimal', $prop->type->toString());
        self::assertSame('Cast', $prop->attrGroups[0]->attrs[0]->name->toString());
        self::assertSame(BigDecimal::class, $prop->getAttribute('fullType'));
    }

    #[Test]
    public function buildPropertyCreatesNullableBigDecimal(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('price')]);
        $column = new DbColumn('price', 'decimal(10,2)', true);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('price', $prop, $column, $this->makeBuilder())
        );

        self::assertInstanceOf(Node\NullableType::class, $prop->type);
        self::assertSame('CastNullable', $prop->attrGroups[0]->attrs[0]->name->toString());
    }

    #[Test]
    public function buildPropertyIgnoresNonDecimalColumns(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('total')]);
        $column = new DbColumn('total', 'bigint', false);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('total', $prop, $column, $this->makeBuilder())
        );

        self::assertEmpty($prop->attrGroups);
        self::assertNull($prop->type);
    }

    #[Test]
    public function buildSetHookUsesColumnScaleAndHalfUp(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('amount')]);
        $column = new DbColumn('amount', 'decimal(12,4)', false);

        $event = new BuildEntityHookEvent(
            \PropertyHookType::Set,
            'amount',
            $prop,
            $column,
            new Node\Name('BigDecimal'),
            $this->makeBuilder(),
        );

        $this->subscriber->buildSetHook($event);

        self::assertNotNull($event->hook);

        /** @var Node\Expr\Assign $assign */
        $assign = $event->hook->body;
        self::assertInstanceOf(Node\Expr\Assign::class, $assign);

        /** @var Node\Expr\FuncCall $call */
        $call = $assign->expr;
        self::assertSame('Windwalker\try_big_decimal', $call->name->toString());
        self::assertSame(4, $call->args[1]->value->value);
        self::assertSame('HalfUp', $call->args[2]->value->name->toString());
    }

    #[Test]
    public function buildSetHookSkipsGetHooks(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('amount')]);
        $column = new DbColumn('amount', 'decimal(12,4)', false);

        $event = new BuildEntityHookEvent(
            \PropertyHookType::Get,
            'amount',
            $prop,
            $column,
            new Node\Name('BigDecimal'),
            $this->makeBuilder(),
        );

        $this->subscriber->buildSetHook($event);

        self::assertNull($event->hook);
    }

    #[Test]
    public function buildSetHookReadsScaleFromDbColumnOptions(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('amount', 'decimal', false, null, ['numeric_scale' => 6]));

        new BigDecimalEntityMemberSubscriber()->buildSetHook($event);

        self::assertSame(6, $this->hookCall($event)->args[1]->value->value);
    }

    #[Test]
    public function buildSetHookUsesConstructorDefaultScaleWhenColumnHasNoScale(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('amount', 'decimal', false));

        new BigDecimalEntityMemberSubscriber(defaultScale: 2)->buildSetHook($event);

        self::assertSame(2, $this->hookCall($event)->args[1]->value->value);
    }

    #[Test]
    public function buildSetHookPrefersDbScaleOverConstructorDefault(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('amount', 'decimal', false, null, ['numeric_scale' => 3]));

        new BigDecimalEntityMemberSubscriber(defaultScale: 2)->buildSetHook($event);

        self::assertSame(3, $this->hookCall($event)->args[1]->value->value);
    }

    #[Test]
    public function buildSetHookUsesConstructorDefaultRoundingMode(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('amount', 'decimal', false));

        new BigDecimalEntityMemberSubscriber(defaultRoundingMode: RoundingMode::Down)->buildSetHook($event);

        self::assertSame('Down', $this->hookCall($event)->args[2]->value->name->toString());
    }

    private function makeSetHookEvent(DbColumn $column): BuildEntityHookEvent
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('amount')]);

        return new BuildEntityHookEvent(
            \PropertyHookType::Set,
            'amount',
            $prop,
            $column,
            new Node\Name('BigDecimal'),
            $this->makeBuilder(),
        );
    }

    private function hookCall(BuildEntityHookEvent $event): Node\Expr\FuncCall
    {
        self::assertNotNull($event->hook);

        /** @var Node\Expr\Assign $assign */
        $assign = $event->hook->body;

        /** @var Node\Expr\FuncCall $call */
        $call = $assign->expr;

        return $call;
    }
}
