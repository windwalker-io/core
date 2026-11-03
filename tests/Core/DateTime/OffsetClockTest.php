<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\MockClock;
use Windwalker\Core\DateTime\OffsetClock;

#[CoversClass(OffsetClock::class)]
class OffsetClockTest extends TestCase
{
    #[Test]
    public function nowReturnsBaseClockTimeWhenNoOffsetAdded(): void
    {
        $clock = new OffsetClock(new MockClock('2020-01-01 10:00:00'));

        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function constructorWrapsPlainValuesThroughClockFrom(): void
    {
        $clock = new OffsetClock('2020-01-01 10:00:00');

        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function addSecondsMutatesOffsetInPlace(): void
    {
        $clock = new OffsetClock(new MockClock('2020-01-01 10:00:00'));

        $returned = $clock->addSeconds(60);

        self::assertSame($clock, $returned);
        self::assertSame('2020-01-01 10:01:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function addSecondsAccumulatesAcrossMultipleCalls(): void
    {
        $clock = new OffsetClock(new MockClock('2020-01-01 10:00:00'));

        $clock->addSeconds(60);
        $clock->addSeconds(30);

        self::assertSame('2020-01-01 10:01:30', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function withAddedSecondsReturnsNewInstanceAndKeepsOriginalUnchanged(): void
    {
        $clock = new OffsetClock(new MockClock('2020-01-01 10:00:00'));

        $new = $clock->withAddedSeconds(120);

        self::assertNotSame($clock, $new);
        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
        self::assertSame('2020-01-01 10:02:00', $new->now()->format('Y-m-d H:i:s'));
    }
}
