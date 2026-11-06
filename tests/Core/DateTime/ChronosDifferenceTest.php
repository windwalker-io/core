<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RoundingMode;
use Windwalker\Core\DateTime\ChronosMath;
use Windwalker\Core\DateTime\DateTimeUnit;
use Windwalker\Core\DateTime\Instant;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateTime;
use Windwalker\Core\DateTime\PlainTime;

#[CoversClass(ChronosMath::class)]
#[CoversClass(DateTimeUnit::class)]
#[CoversClass(PlainDate::class)]
#[CoversClass(PlainTime::class)]
#[CoversClass(PlainDateTime::class)]
#[CoversClass(Instant::class)]
class ChronosDifferenceTest extends TestCase
{
    private static function fmt(DateInterval $i): string
    {
        return $i->format('%R %yy %mm %dd %hh %im %ss %Fus');
    }

    #[Test]
    public function plainDateUntilAndSince(): void
    {
        $a = PlainDate::of(2026, 1, 31);

        self::assertSame('+ 0y 0m 29d 0h 0m 0s 000000us', self::fmt($a->until(PlainDate::of(2026, 3, 1))));
        self::assertSame(
            '+ 0y 1m 1d 0h 0m 0s 000000us',
            self::fmt($a->until(PlainDate::of(2026, 3, 1), 'month'))
        );
        self::assertSame(
            '+ 1y 2m 3d 0h 0m 0s 000000us',
            self::fmt(PlainDate::of(2025, 1, 1)->until(PlainDate::of(2026, 3, 4), DateTimeUnit::Year))
        );
        self::assertSame(
            '- 0y 0m 10d 0h 0m 0s 000000us',
            self::fmt(PlainDate::of(2026, 3, 11)->until(PlainDate::of(2026, 3, 1)))
        );
        self::assertSame(
            '+ 0y 0m 10d 0h 0m 0s 000000us',
            self::fmt(PlainDate::of(2026, 3, 11)->since(PlainDate::of(2026, 3, 1)))
        );
        self::assertSame(
            '+ 0y 0m 10d 0h 0m 0s 000000us',
            self::fmt(PlainDate::of(2026, 3, 11)->diff(PlainDate::of(2026, 3, 1), absolute: true))
        );
    }

    #[Test]
    public function plainDateRoundsToMonthAndYear(): void
    {
        $a = PlainDate::of(2026, 1, 1);

        self::assertSame(
            '+ 0y 1m 0d 0h 0m 0s 000000us',
            self::fmt($a->until(PlainDate::of(2026, 2, 10), 'month', 'month', roundingMode: RoundingMode::HalfAwayFromZero))
        );
        self::assertSame(
            '+ 0y 0m 0d 0h 0m 0s 000000us',
            self::fmt($a->until(PlainDate::of(2026, 1, 10), 'month', 'month'))
        );
        self::assertSame(
            '+ 1y 0m 0d 0h 0m 0s 000000us',
            self::fmt($a->until(PlainDate::of(2026, 12, 1), 'year', 'year', roundingMode: RoundingMode::AwayFromZero))
        );
    }

    #[Test]
    public function plainDateRejectsInvalidUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlainDate::of(2026, 1, 1)->until(PlainDate::of(2026, 1, 2), 'hour');
    }

    #[Test]
    public function largestUnitMustNotBeSmallerThanSmallestUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlainDateTime::of(2026, 1, 1)->until(PlainDateTime::of(2026, 1, 2), 'hour', 'day');
    }

    #[Test]
    public function plainDateRejectsOtherTypes(): void
    {
        $this->expectException(\TypeError::class);

        PlainDate::of(2026, 1, 1)->until(PlainTime::of(1, 0));
    }

    #[Test]
    public function plainDateTimeUntil(): void
    {
        $a = PlainDateTime::parse('2026-01-31T10:00:00');
        $b = PlainDateTime::parse('2026-03-02T09:30:15.5');

        self::assertSame('+ 0y 1m 1d 23h 30m 15s 500000us', self::fmt($a->until($b, 'month')));
        self::assertSame('+ 0y 0m 29d 23h 30m 15s 500000us', self::fmt($a->until($b)));
        self::assertSame('+ 0y 0m 0d 719h 30m 15s 500000us', self::fmt($a->until($b, 'hour')));
        self::assertSame('- 0y 0m 29d 23h 30m 15s 500000us', self::fmt($a->since($b)));
    }

    #[Test]
    public function plainDateTimeCarriesRoundingIntoLargerUnits(): void
    {
        $a = PlainDateTime::parse('2026-01-01T00:00:00');
        $b = PlainDateTime::parse('2026-01-31T23:40:00');

        self::assertSame(
            '+ 0y 1m 0d 0h 0m 0s 000000us',
            self::fmt($a->until($b, 'month', 'hour', roundingMode: RoundingMode::HalfAwayFromZero))
        );
    }

    #[Test]
    public function plainTimeUntil(): void
    {
        $a = PlainTime::of(10, 0);
        $b = PlainTime::of(12, 30, 45);

        self::assertSame('+ 0y 0m 0d 2h 30m 45s 000000us', self::fmt($a->until($b)));
        self::assertSame('+ 0y 0m 0d 0h 150m 0s 000000us', self::fmt($a->until($b, 'minute', 'minute')));
        self::assertSame('- 0y 0m 0d 2h 30m 45s 000000us', self::fmt($a->since($b)));
        self::assertSame('+ 0y 0m 0d 3h 0m 0s 000000us', self::fmt($a->until($b, 'hour', 'hour', roundingMode: RoundingMode::AwayFromZero)));
    }

    #[Test]
    public function instantUntilUsesSecondsAsDefaultLargestUnit(): void
    {
        $a = Instant::parse('2026-01-01T00:00:00Z');
        $b = Instant::parse('2026-01-02T01:00:00.25Z');

        self::assertSame('+ 0y 0m 0d 0h 0m 90000s 250000us', self::fmt($a->until($b)));
        self::assertSame('+ 0y 0m 0d 25h 0m 0s 250000us', self::fmt($a->until($b, 'hour')));
        self::assertSame('- 0y 0m 0d 25h 0m 0s 250000us', self::fmt($a->since($b, 'hour')));
        self::assertSame('+ 0y 0m 0d 25h 0m 0s 000000us', self::fmt($a->until($b, 'hour', 'second')));

        // Accepts any exact point, including native DateTime
        self::assertSame(
            '+ 0y 0m 0d 1h 0m 0s 000000us',
            self::fmt($a->until(new \DateTimeImmutable('2026-01-01 09:00:00', new \DateTimeZone('Asia/Taipei')), 'hour'))
        );
    }

    #[Test]
    public function instantRejectsCalendarUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Instant::now()->until(Instant::now(), 'day');
    }

    #[Test]
    public function plainTimeRoundWrapsAroundMidnight(): void
    {
        self::assertSame('10:30:00', PlainTime::parse('10:29:31')->round('minute')->toIso());
        self::assertSame('00:00:00', PlainTime::parse('23:59:59.6')->round('second')->toIso());
        self::assertSame('10:15:00', PlainTime::parse('10:07:30')->round('minute', 15)->toIso());
        self::assertSame('10:00:00', PlainTime::parse('10:29:31')->round('hour', roundingMode: RoundingMode::NegativeInfinity)->toIso());
        self::assertSame('10:30:00.123000', PlainTime::parse('10:30:00.123456')->round('millisecond')->toIso());
    }

    #[Test]
    public function plainDateTimeRound(): void
    {
        $dt = PlainDateTime::parse('2026-01-31T13:00:00');

        self::assertSame('2026-02-01T00:00:00', $dt->round('day')->toIso());
        self::assertSame('2026-01-31T00:00:00', $dt->round('day', roundingMode: RoundingMode::TowardsZero)->toIso());
        self::assertSame('2026-01-31T13:00:00', $dt->round('second')->toIso());
    }

    #[Test]
    public function instantRound(): void
    {
        self::assertSame(
            '2026-01-01T01:00:00Z',
            Instant::parse('2026-01-01T00:30:00Z')->round('hour')->toIso()
        );
        self::assertSame(
            '1969-12-31T23:59:59.500000Z',
            Instant::fromEpochMilliseconds(-500)->round('millisecond')->toIso()
        );
    }

    #[Test]
    public function roundRejectsUnsupportedUnitsAndIncrement(): void
    {
        try {
            Instant::now()->round('day');
            self::fail('Expected exception');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->expectException(InvalidArgumentException::class);
        PlainTime::now()->round('minute', 0);
    }

    #[Test]
    public function instantFromEpochSecondsPadsMicroseconds(): void
    {
        self::assertSame(5, Instant::fromEpochSeconds(100, 5)->getMicrosecond());
        self::assertSame('000005', Instant::fromEpochSeconds(100, 5)->microsecond);
    }

    #[DataProvider('roundingProvider')]
    #[Test]
    public function roundDiv(int $n, int $d, RoundingMode $mode, int $expected): void
    {
        self::assertSame($expected, ChronosMath::roundDiv($n, $d, $mode));
    }

    public static function roundingProvider(): array
    {
        return [
            [5, 10, RoundingMode::HalfAwayFromZero, 1],
            [-5, 10, RoundingMode::HalfAwayFromZero, -1],
            [5, 10, RoundingMode::HalfTowardsZero, 0],
            [-5, 10, RoundingMode::HalfTowardsZero, 0],
            [15, 10, RoundingMode::HalfEven, 2],
            [25, 10, RoundingMode::HalfEven, 2],
            [25, 10, RoundingMode::HalfOdd, 3],
            [-3, 10, RoundingMode::NegativeInfinity, -1],
            [-3, 10, RoundingMode::PositiveInfinity, 0],
            [-3, 10, RoundingMode::TowardsZero, 0],
            [-3, 10, RoundingMode::AwayFromZero, -1],
            [30, 10, RoundingMode::AwayFromZero, 3],
        ];
    }

    #[Test]
    public function unitResolvesPluralAndRejectsUnknown(): void
    {
        self::assertSame(DateTimeUnit::Day, DateTimeUnit::resolve('days'));
        self::assertSame(DateTimeUnit::Microsecond, DateTimeUnit::resolve('Microseconds'));

        $this->expectException(InvalidArgumentException::class);
        DateTimeUnit::resolve('week');
    }

    #[Test]
    public function microsecondsSurviveRoundTrip(): void
    {
        $a = PlainDateTime::of(2024, 11, 15, 20, 27, 3, 554537);
        $b = PlainDateTime::of(2026, 12, 23, 8, 50, 45, 69929);

        self::assertTrue($a->plus($a->until($b, 'year'))->equals($b));
        self::assertTrue($b->plus($b->until($a, 'year'))->equals($a));
    }
}
