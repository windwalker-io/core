<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\DateTime\DateOverflow;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateTime;
use Windwalker\Core\DateTime\PlainMonthDay;
use Windwalker\Core\DateTime\PlainYearMonth;

#[CoversClass(PlainYearMonth::class)]
#[CoversClass(PlainMonthDay::class)]
class PlainYearMonthDayTest extends TestCase
{
    #[Test]
    public function yearMonthBasics(): void
    {
        $ym = PlainYearMonth::of(2024, 2);

        self::assertSame(['2024', '02', '29', '366', '12', true], [
            $ym->year, $ym->month, $ym->daysInMonth, $ym->daysInYear, $ym->monthsInYear, $ym->inLeapYear,
        ]);
        self::assertSame([2024, 2, 29, 366], [
            $ym->getYear(), $ym->getMonth(), $ym->getDaysInMonth(), $ym->getDaysInYear(),
        ]);
        self::assertSame('2024-02', (string) $ym);
        self::assertSame('"2024-02"', json_encode($ym));
        self::assertSame('2024-02-29', (string) $ym->endOfMonth());
        self::assertSame('2024-02-10', (string) $ym->toPlainDate(10));
        self::assertSame('2024-02-29', (string) $ym->toPlainDate(31));
    }

    #[Test]
    public function yearMonthParseAndFrom(): void
    {
        self::assertSame('2024-03', PlainYearMonth::parse('2024-03')->toIso());
        self::assertSame('2024-03', PlainYearMonth::parse('2024-03-31T10:00:00+08:00')->toIso());
        self::assertSame('2024-03', PlainYearMonth::from(PlainDate::of(2024, 3, 15))->toIso());
        self::assertSame('2024-03', PlainYearMonth::from(new \DateTimeImmutable('2024-03-15 23:00'))->toIso());
        self::assertSame('2024-03', PlainDateTime::parse('2024-03-15T01:02')->toPlainYearMonth()->toIso());
        self::assertSame('2024-03', PlainDate::of(2024, 3, 15)->toPlainYearMonth()->toIso());

        foreach (['2024-13', '2024-00', '2024', 'abc', '2024-3'] as $bad) {
            try {
                PlainYearMonth::parse($bad);
                self::fail($bad);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    #[Test]
    public function yearMonthWithAndArithmetic(): void
    {
        $ym = PlainYearMonth::of(2024, 11);

        self::assertSame('2025-01', $ym->plus('P2M')->toIso());
        self::assertSame('2023-11', $ym->minus('P1Y')->toIso());
        self::assertSame('2024-01', $ym->minus('P10M')->toIso());
        self::assertSame('2023-12', PlainYearMonth::of(2024, 1)->minus('P1M')->toIso());
        self::assertSame('2025-03', $ym->with(2025, 3)->toIso());
        self::assertSame('2024-12', $ym->with(month: 99)->toIso());

        $this->expectException(\RangeException::class);
        $ym->with(month: 13, overflow: DateOverflow::Reject);
    }

    #[Test]
    public function yearMonthRejectsDays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainYearMonth::of(2024, 1)->plus('P1D');
    }

    #[Test]
    public function yearMonthCompareAndDifference(): void
    {
        $a = PlainYearMonth::of(2024, 1);
        $b = PlainYearMonth::of(2025, 4);

        self::assertTrue($a->isBefore($b));
        self::assertTrue($a->equals(PlainYearMonth::of(2024, 1)));
        self::assertSame(-1, PlainYearMonth::compare($a, $b));

        $sorted = [$b, $a];
        usort($sorted, PlainYearMonth::compare(...));
        self::assertSame('2024-01', $sorted[0]->toIso());

        $until = $a->until($b);
        self::assertSame([1, 3, 0], [$until->y, $until->m, $until->invert]);

        $months = $a->until($b, 'months');
        self::assertSame([0, 15], [$months->y, $months->m]);

        $since = $a->since($b);
        self::assertSame([1, 3, 1], [$since->y, $since->m, $since->invert]);

        $rounded = $a->until(PlainYearMonth::of(2024, 8), 'years', 'years', 1, \RoundingMode::HalfAwayFromZero);
        self::assertSame([1, 0], [$rounded->y, $rounded->m]);

        $diff = $a->diff(PlainYearMonth::of(2024, 3));
        self::assertSame([0, 2, 60], [$diff->y, $diff->m, $diff->days]);

        self::assertSame(0, ChronosService::compare($a, '2024-01'));
        self::assertTrue(ChronosService::compare($a, $b, '<'));

        $this->expectException(InvalidArgumentException::class);
        $a->until($b, 'days');
    }

    #[Test]
    public function yearMonthRejectsOtherTypes(): void
    {
        $this->expectException(\TypeError::class);
        PlainYearMonth::of(2024, 1)->compareTo(PlainDate::of(2024, 1, 1));
    }

    #[Test]
    public function monthDayBasics(): void
    {
        $md = PlainMonthDay::of(2, 29);

        self::assertSame(['02', '29', 2, 29], [$md->month, $md->day, $md->getMonth(), $md->getDay()]);
        self::assertSame('02-29', (string) $md);
        self::assertSame('"02-29"', json_encode($md));
        self::assertSame('2024-02-29', (string) $md->toPlainDate(2024));
        self::assertSame('2025-02-28', (string) $md->toPlainDate(2025));
        self::assertTrue($md->equals(PlainMonthDay::parse('--02-29')));
        self::assertFalse($md->equals(PlainMonthDay::of(3, 1)));
    }

    #[Test]
    public function monthDayParseAndWith(): void
    {
        self::assertSame('12-25', PlainMonthDay::parse('12-25')->toIso());
        self::assertSame('12-25', PlainMonthDay::parse('--12-25')->toIso());
        self::assertSame('12-25', PlainMonthDay::parse('2026-12-25T10:00')->toIso());
        self::assertSame('12-25', PlainDate::of(2026, 12, 25)->toPlainMonthDay()->toIso());
        self::assertSame('12-25', PlainDateTime::parse('2026-12-25T10:00')->toPlainMonthDay()->toIso());
        self::assertSame('02-29', PlainMonthDay::of(1, 31)->with(month: 2)->toIso());
        self::assertSame('04-30', PlainMonthDay::of(1, 31)->with(month: 4)->toIso());

        foreach (['02-30', '13-01', '1-1', 'abc', '2025-02-29'] as $bad) {
            try {
                PlainMonthDay::parse($bad);
                self::fail($bad);
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    #[Test]
    public function monthDayRejectsInvalid(): void
    {
        $this->expectException(\RangeException::class);
        PlainMonthDay::of(2, 30);
    }

    #[Test]
    public function parseRejectsTrailingNewlineAndSpaces(): void
    {
        $bads = [
            [PlainYearMonth::class, "2024-03\n"],
            [PlainMonthDay::class, "03-01\n"],
            [PlainDate::class, "2024-03-01\n"],
            [PlainDateTime::class, "2024-03-01T10:00\n"],
            [\Windwalker\Core\DateTime\PlainTime::class, "10:00\n"],
            [\Windwalker\Core\DateTime\Instant::class, "2024-03-01T10:00:00Z\n"],
            [\Windwalker\Core\DateTime\Instant::class, ' 2024-03-01T10:00:00Z'],
        ];

        foreach ($bads as [$class, $value]) {
            try {
                $class::parse($value);
                self::fail($class . ' ' . json_encode($value));
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    #[Test]
    public function roundIncrementMustDivideNextUnit(): void
    {
        $dt = PlainDateTime::parse('2024-01-01T10:20');

        self::assertSame('2024-01-01T10:15:00', $dt->round('minutes', 15)->toIso());
        self::assertSame('2024-01-01T12:00:00', $dt->round('hours', 12)->toIso());

        foreach ([['hours', 7], ['hours', 24], ['minutes', 90], ['minutes', 7], ['days', 2], ['milliseconds', 3]] as [$unit, $inc]) {
            try {
                $dt->round($unit, $inc);
                self::fail("$unit $inc");
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

        $this->expectException(InvalidArgumentException::class);
        $dt->until(PlainDateTime::parse('2024-01-02'), 'hours', 'hours', 7);
    }
}
