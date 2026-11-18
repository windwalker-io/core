<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Generator\Subscriber;

use Brick\Math\BigInteger;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Generator\Builder\EntityMemberBuilder;
use Windwalker\Core\Generator\Event\BuildEntityHookEvent;
use Windwalker\Core\Generator\Event\BuildEntityPropertyEvent;
use Windwalker\Core\Generator\Subscriber\BigIntegerEntityMemberSubscriber;
use Windwalker\Database\Schema\Ddl\Column as DbColumn;
use Windwalker\ORM\Metadata\EntityMetadata;

/**
 * Covers BigIntegerEntityMemberSubscriber: property type and set hook generation for bigint columns.
 */
class BigIntegerEntityMemberSubscriberTest extends TestCase
{
    private BigIntegerEntityMemberSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new BigIntegerEntityMemberSubscriber();
    }

    private function makeBuilder(): EntityMemberBuilder
    {
        return new EntityMemberBuilder($this->createStub(EntityMetadata::class));
    }

    #[Test]
    public function buildPropertyCreatesNonNullableBigInteger(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('total')]);
        $column = new DbColumn('total', 'bigint', false);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('total', $prop, $column, $this->makeBuilder())
        );

        self::assertSame('BigInteger', $prop->type->toString());
        self::assertSame('Cast', $prop->attrGroups[0]->attrs[0]->name->toString());
        self::assertSame(BigInteger::class, $prop->getAttribute('fullType'));
    }

    #[Test]
    public function buildPropertyCreatesNullableBigInteger(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('total')]);
        $column = new DbColumn('total', 'bigint', true);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('total', $prop, $column, $this->makeBuilder())
        );

        self::assertInstanceOf(Node\NullableType::class, $prop->type);
        self::assertSame('CastNullable', $prop->attrGroups[0]->attrs[0]->name->toString());
    }

    #[Test]
    public function buildPropertyIgnoresNonBigIntColumns(): void
    {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('total')]);
        $column = new DbColumn('total', 'decimal', false);

        $this->subscriber->buildProperty(
            new BuildEntityPropertyEvent('total', $prop, $column, $this->makeBuilder())
        );

        self::assertEmpty($prop->attrGroups);
        self::assertNull($prop->type);
    }

    #[Test]
    public function buildSetHookAcceptsNullAndCastsToBigInteger(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('total', 'bigint', true));

        $this->subscriber->buildSetHook($event);

        self::assertNotNull($event->hook);

        /** @var Node\Expr\Assign $assign */
        $assign = $event->hook->body;
        self::assertInstanceOf(Node\Expr\Assign::class, $assign);

        // Delegates to the core helper, which passes null through and casts other values.
        self::assertInstanceOf(Node\Expr\FuncCall::class, $assign->expr);
        self::assertSame('Windwalker\try_big_integer', $assign->expr->name->toString());
    }

    #[Test]
    public function buildSetHookSkipsGetHooks(): void
    {
        $event = $this->makeSetHookEvent(new DbColumn('total', 'bigint', false), \PropertyHookType::Get);

        $this->subscriber->buildSetHook($event);

        self::assertNull($event->hook);
    }

    private function makeSetHookEvent(
        DbColumn $column,
        \PropertyHookType $hookType = \PropertyHookType::Set,
    ): BuildEntityHookEvent {
        $prop = new Node\Stmt\Property(Node\Stmt\Class_::MODIFIER_PUBLIC, [new Node\PropertyItem('total')]);

        return new BuildEntityHookEvent(
            $hookType,
            'total',
            $prop,
            $column,
            new Node\Name('BigInteger'),
            $this->makeBuilder(),
        );
    }
}
