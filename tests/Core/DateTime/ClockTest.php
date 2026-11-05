<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\Clock;
use Windwalker\Core\DateTime\MockClock;
use Windwalker\Core\DateTime\SystemClock;

#[CoversClass(Clock::class)]
class ClockTest extends TestCase
{
    #[After]
    protected function resetClock(): void
    {
        Clock::reset();
    }

    #[Test]
    public function isSystemIsTrueBeforeAnyClockIsSet(): void
    {
        self::assertTrue(Clock::isSystem());
    }

    #[Test]
    public function nowUsesSystemClockByDefault(): void
    {
        $result = Clock::now();

        self::assertInstanceOf(Chronos::class, $result);
        self::assertTrue(Clock::isSystem());
    }

    #[Test]
    public function nowUsesConfiguredMockClock(): void
    {
        Clock::set('2020-06-15 12:00:00');

        self::assertFalse(Clock::isSystem());
        self::assertSame('2020-06-15 12:00:00', Clock::now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function nowAppliesGivenTimezone(): void
    {
        Clock::set(new Chronos('2020-06-15 12:00:00', 'UTC'));

        $result = Clock::now('Asia/Taipei');

        self::assertSame('2020-06-15 20:00:00', $result->format('Y-m-d H:i:s'));
        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
    }

    #[Test]
    public function modernEntryPointsFollowTheConfiguredClock(): void
    {
        Clock::set(new Chronos('2020-06-15 23:30:45.123456', 'UTC'));

        self::assertSame('2020-06-15T23:30:45.123456Z', Clock::instant()->toIso());
        self::assertSame('2020-06-15', Clock::plainDate('UTC')->toIso());
        self::assertSame('2020-06-16', Clock::plainDate('Asia/Taipei')->toIso());
        self::assertSame('23:30:45.123456', Clock::plainTime('UTC')->toIso());
        self::assertSame('07:30:45.123456', Clock::plainTime('Asia/Taipei')->toIso());
        self::assertSame('2020-06-16T07:30:45.123456', Clock::plainDateTime('Asia/Taipei')->toIso());
    }

    #[Test]
    public function fromReturnsSystemClockForNull(): void
    {
        self::assertInstanceOf(SystemClock::class, Clock::from(null));
    }

    #[Test]
    public function fromReturnsSameClockInterfaceInstance(): void
    {
        $mock = new MockClock('2020-01-01');

        self::assertSame($mock, Clock::from($mock));
    }

    #[Test]
    public function fromWrapsNonClockValuesInMockClock(): void
    {
        $clock = Clock::from('2020-01-01 10:00:00');

        self::assertInstanceOf(MockClock::class, $clock);
        self::assertSame('2020-01-01 10:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function isSystemIsFalseAfterNonSystemClockIsSet(): void
    {
        Clock::set(new MockClock('2020-01-01'));

        self::assertFalse(Clock::isSystem());
    }
}
