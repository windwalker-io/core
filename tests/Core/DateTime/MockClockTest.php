<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\MockClock;

#[CoversClass(MockClock::class)]
class MockClockTest extends TestCase
{
    #[Test]
    public function constructorAcceptsStringDate(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function constructorAcceptsTimestampInt(): void
    {
        $clock = new MockClock(0);

        self::assertSame('1970-01-01 00:00:00', $clock->now()->setTimezone('UTC')->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function constructorAcceptsDateTimeInterface(): void
    {
        $date = new DateTimeImmutable('2020-05-05 08:30:00');

        $clock = new MockClock($date);

        self::assertSame('2020-05-05 08:30:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function setMutatesCurrentTimeInPlace(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $returned = $clock->set('2021-02-02 11:00:00');

        self::assertSame($clock, $returned);
        self::assertSame('2021-02-02 11:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function withReturnsNewInstanceAndKeepsOriginalUnchanged(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $new = $clock->with('2022-03-03 12:00:00');

        self::assertNotSame($clock, $new);
        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
        self::assertSame('2022-03-03 12:00:00', $new->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function modifyMutatesCurrentTimeInPlace(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $returned = $clock->modify('+1 day');

        self::assertSame($clock, $returned);
        self::assertSame('2020-01-02 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function withModifyReturnsNewInstanceAndKeepsOriginalUnchanged(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $new = $clock->withModify('+1 day');

        self::assertNotSame($clock, $new);
        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
        self::assertSame('2020-01-02 10:00:00', $new->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function sleepAddsSecondsInPlace(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $returned = $clock->sleep(90);

        self::assertSame($clock, $returned);
        self::assertSame('2020-01-01 10:01:30', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function withSleepReturnsNewInstanceAndKeepsOriginalUnchanged(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00');

        $new = $clock->withSleep(60);

        self::assertNotSame($clock, $new);
        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
        self::assertSame('2020-01-01 10:01:00', $new->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function usleepAddsMicrosecondsInPlace(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00.000000');

        $returned = $clock->usleep(500000);

        self::assertSame($clock, $returned);
        self::assertSame('2020-01-01 10:00:00.500000', $clock->now()->format('Y-m-d H:i:s.u'));
    }

    #[Test]
    public function withUsleepReturnsNewInstanceAndKeepsOriginalUnchanged(): void
    {
        $clock = new MockClock('2020-01-01 10:00:00.000000');

        $new = $clock->withUsleep(250000);

        self::assertNotSame($clock, $new);
        self::assertSame('2020-01-01 10:00:00.000000', $clock->now()->format('Y-m-d H:i:s.u'));
        self::assertSame('2020-01-01 10:00:00.250000', $new->now()->format('Y-m-d H:i:s.u'));
    }
}
