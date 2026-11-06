<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Time\Duration;
use Windwalker\Core\DateTime\ChronosMath;
use Windwalker\Core\DateTime\Instant;
use Windwalker\Core\DateTime\DateOverflow;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateTime;
use Windwalker\Core\DateTime\PlainTime;

#[CoversClass(PlainDate::class)]
#[CoversClass(PlainTime::class)]
#[CoversClass(PlainDateTime::class)]
#[CoversClass(Instant::class)]
#[CoversClass(ChronosMath::class)]
class ChronoValueObjectsTest extends TestCase
{
    #[Test]
    public function plainDateFieldsAndParse(): void
    {
        $d = PlainDate::parse('2026-03-05');

        self::assertSame([2026, 3, 5, 4, 31], [$d->getYear(), $d->getMonth(), $d->getDay(), $d->getDayOfWeek(), $d->getDaysInMonth()]);
        self::assertSame(['2026', '03', '05', '4', '31'], [$d->year, $d->month, $d->day, $d->dayOfWeek, $d->daysInMonth]);
        self::assertSame('2026-03-05', (string) $d);
        self::assertSame('"2026-03-05"', json_encode($d));
    }

    #[Test]
    public function chronosImplementsModernInterfaces(): void
    {
        $c = new \Windwalker\Core\DateTime\Chronos('2026-01-31 09:05:03.5', new DateTimeZone('Asia/Taipei'));

        self::assertSame(['2026', '01', '31', '09', '05', '03', '500000'], [
            $c->year, $c->month, $c->day, $c->hour, $c->minute, $c->second, $c->microsecond,
        ]);
        self::assertSame([2026, 1, 31, 9, 5, 3, 500000], [
            $c->getYear(), $c->getMonth(), $c->getDay(), $c->getHour(), $c->getMinute(), $c->getSecond(),
            $c->getMicrosecond(),
        ]);
        self::assertSame('2026-02-28 09:05:03', $c->plus('P1M')->format('Y-m-d H:i:s'));
        self::assertSame('2026-03-03', $c->plus('P1M', DateOverflow::Native)->format('Y-m-d'));
        self::assertSame('2026-01-31T01:05:03.500000Z', $c->toInstant()->toIso());
        self::assertSame('2026-01-31', $c->toPlainDate()->toIso());
        self::assertTrue($c->equals($c->toInstant()));
        self::assertTrue($c->isBefore($c->plus('PT1S')));
    }

    #[Test]
    public function plainDateRejectsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlainDate::parse('2026-02-31');
    }

    #[Test]
    public function plainDateOfOverflow(): void
    {
        self::assertSame('2026-02-28', PlainDate::of(2026, 2, 31, DateOverflow::Constrain)->toIso());
        self::assertSame('2026-03-03', PlainDate::of(2026, 2, 31, DateOverflow::Native)->toIso());

        $this->expectException(\RangeException::class);
        PlainDate::of(2026, 2, 31);
    }

    #[Test]
    public function plusConstrainsByDefaultAndNativeOverflows(): void
    {
        $d = PlainDate::of(2026, 1, 31);

        self::assertSame('2026-02-28', $d->plus(new DateInterval('P1M'))->toIso());
        self::assertSame('2026-03-03', $d->plus(new DateInterval('P1M'), DateOverflow::Native)->toIso());
        self::assertSame('2025-02-28', PlainDate::of(2024, 2, 29)->plus('P1Y')->toIso());
        self::assertSame('2025-12-31', $d->minus('1 month')->toIso());
        self::assertSame('2026-01-28', $d->plus('P1M')->minus('P1M')->toIso());
    }

    #[Test]
    public function plusRejectOverflow(): void
    {
        $this->expectException(\RangeException::class);

        PlainDate::of(2026, 1, 31)->plus('P1M', DateOverflow::Reject);
    }

    #[Test]
    public function plusAcceptsStringAndSignedUnits(): void
    {
        $d = PlainDate::of(2026, 1, 1);

        self::assertSame('2026-02-04', $d->plus('1 month 3 days')->toIso());
        self::assertSame('2025-12-18', $d->plus('2 weeks ago')->toIso());
        self::assertSame('2025-12-31', $d->minus('P1D')->toIso());
    }

    #[Test]
    public function plusRejectsNonAdditiveString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlainDate::of(2026, 1, 1)->plus('next monday');
    }

    #[Test]
    public function plainDateRejectsTimeUnits(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PlainDate::of(2026, 1, 1)->plus('PT1H');
    }

    #[Test]
    public function withConstrainsDay(): void
    {
        $d = PlainDate::of(2026, 3, 31);

        self::assertSame('2026-02-28', $d->with(month: 2)->toIso());
        self::assertSame('2026-02-01', $d->with(month: 2, day: 1)->toIso());
    }

    #[Test]
    public function plainTimeWrapsAround(): void
    {
        $t = PlainTime::parse('23:30:15.5');

        self::assertSame([23, 30, 15, 500000], [$t->getHour(), $t->getMinute(), $t->getSecond(), $t->getMicrosecond()]);
        self::assertSame(['23', '30', '15', '500000'], [$t->hour, $t->minute, $t->second, $t->microsecond]);
        self::assertSame('09', PlainTime::of(9, 5)->hour);
        self::assertSame('00:15:15.500000', $t->plus('PT45M')->toIso());
        self::assertSame('23:30:15', $t->with(microsecond: 0)->toIso());

        $this->expectException(InvalidArgumentException::class);
        $t->plus('P1D');
    }

    #[Test]
    public function plainDateTimeFieldsAndMath(): void
    {
        $dt = PlainDateTime::parse('2026-01-31T10:20:30');

        self::assertSame('2026-02-28T10:20:30', $dt->plus('P1M')->toIso());
        self::assertSame('2026-03-03T10:20:30', $dt->plus('P1M', DateOverflow::Native)->toIso());
        self::assertSame('2026-01-31T09:20:30', $dt->minus(Duration::fromSeconds(3600))->toIso());
        self::assertSame('2026-01-31', $dt->toPlainDate()->toIso());
        self::assertSame('10:20:30', $dt->toPlainTime()->toIso());
        self::assertTrue($dt->equals(PlainDateTime::combine($dt->toPlainDate(), $dt->toPlainTime())));
    }

    #[Test]
    public function plainDateTimeIsNotAffectedByDst(): void
    {
        // 2026-03-08 is the US DST start day
        $dt = PlainDateTime::of(2026, 3, 8, 1, 30);

        self::assertSame('2026-03-08T03:30:00', $dt->plus('PT2H')->toIso());
        self::assertSame('2026-03-09T01:30:00', $dt->plus('P1D')->toIso());
    }

    #[Test]
    public function compareOnlyBetweenSameType(): void
    {
        $a = PlainDate::of(2026, 1, 1);
        $b = PlainDate::of(2026, 1, 2);

        self::assertTrue($a->isBefore($b));
        self::assertTrue($b->isAfter($a));
        self::assertTrue($a->equals(PlainDate::parse('2026-01-01')));
        self::assertLessThan(0, $a->compareTo($b));

        $this->expectException(\TypeError::class);
        $a->compareTo(PlainTime::of(1, 0));
    }

    #[Test]
    public function plainTypesRefuseDateTimeInterface(): void
    {
        $this->expectException(\TypeError::class);

        PlainDate::of(2026, 1, 1)->isBefore(new DateTimeImmutable());
    }

    #[Test]
    public function instantFromEpochAndIso(): void
    {
        $i = Instant::fromEpochSeconds(1_767_225_600, 123456);

        self::assertSame('2026-01-01T00:00:00.123456Z', $i->toIso());
        self::assertSame(1_767_225_600, $i->getEpochSeconds());
        self::assertSame('1767225600', $i->epochSeconds);
        self::assertSame(123456, $i->getMicrosecond());
        self::assertSame(1_767_225_600_123, $i->toEpochMilliseconds());
        self::assertSame('2026-01-01T00:00:00Z', Instant::fromEpochMilliseconds(1_767_225_600_000)->toIso());
        self::assertSame(
            '1969-12-31T23:59:59.500000Z',
            Instant::fromEpochMilliseconds(-500)->toIso()
        );
    }

    #[Test]
    public function instantParseNormalizesOffset(): void
    {
        $i = Instant::parse('2026-01-01T08:00:00+08:00');

        self::assertSame('2026-01-01T00:00:00Z', $i->toIso());
        self::assertSame('2026-01-01T00:00:00Z', Instant::parse('2026-01-01T00:00:00Z')->toIso());
        self::assertSame(500000, Instant::parse('2026-01-01T00:00:00.5Z')->getMicrosecond());
    }

    #[Test]
    public function instantParseRequiresTimeZone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Instant::parse('2026-01-01T00:00:00');
    }

    #[Test]
    public function instantComparesWithDateTimeInterface(): void
    {
        $i = Instant::parse('2026-01-01T00:00:00Z');
        $same = new DateTimeImmutable('2026-01-01 08:00:00', new DateTimeZone('Asia/Taipei'));

        self::assertTrue($i->equals($same));
        self::assertTrue($i->isBefore(new DateTimeImmutable('2026-01-01 00:00:00.000001 UTC')));
        self::assertTrue($i->isAfter(new DateTimeImmutable('2025-12-31 23:59:59.999999 UTC')));
    }

    #[Test]
    public function instantAcceptsOnlyTimeUnits(): void
    {
        $i = Instant::parse('2026-01-01T00:00:00Z');

        self::assertSame('2026-01-01T01:30:00Z', $i->plus('PT1H30M')->toIso());
        self::assertSame('2025-12-31T23:59:59Z', $i->minus(Duration::fromSeconds(1))->toIso());

        $this->expectException(InvalidArgumentException::class);
        $i->plus('P1D');
    }

    #[Test]
    public function instantConvertsToDateTime(): void
    {
        $dt = Instant::parse('2026-01-01T00:00:00Z')->toDateTime('Asia/Taipei');

        self::assertSame('2026-01-01 08:00:00', $dt->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function conversionFunctions(): void
    {
        $d = PlainDate::of(2026, 3, 5);
        self::assertSame($d, \Windwalker\to_plain_date($d));
        self::assertSame('2026-03-05', \Windwalker\to_plain_date('2026-03-05')->toIso());

        // wall-clock date, no UTC conversion
        $c = \Windwalker\Core\DateTime\Chronos::create('2026-03-05 03:00:00', 'Asia/Taipei');
        self::assertSame('2026-03-05', \Windwalker\to_plain_date($c)->toIso());
        self::assertSame('03:00:00', \Windwalker\to_plain_time($c)->toIso());
        self::assertSame('2026-03-05T03:00:00', \Windwalker\to_plain_datetime($c)->toIso());
        self::assertSame('2026-03-04T19:00:00Z', \Windwalker\to_instant($c)->toIso());

        self::assertSame('2026-03-05', \Windwalker\to_plain_date(PlainDateTime::parse('2026-03-05T10:00:00'))->toIso());

        self::assertNull(\Windwalker\try_instant(null));
        self::assertNull(\Windwalker\try_plain_date(''));
        self::assertNull(\Windwalker\try_plain_time(null));
        self::assertNull(\Windwalker\try_plain_datetime(''));
    }

    #[Test]
    public function conversionFunctionsRejectLossyInput(): void
    {
        $this->expectException(\TypeError::class);
        \Windwalker\to_instant(PlainDate::of(2026, 3, 5));
    }

    #[Test]
    public function toChronos(): void
    {
        $i = Instant::parse('2026-01-01T00:00:00Z')->toChronos();
        self::assertSame('UTC', $i->getTimezone()->getName());
        self::assertSame('2026-01-01 08:00', Instant::parse('2026-01-01T00:00:00Z')->toChronos('Asia/Taipei')->format('Y-m-d H:i'));

        $d = PlainDate::of(2026, 3, 5)->toChronos('Asia/Taipei');
        self::assertSame('2026-03-05 00:00:00 +08:00', $d->format('Y-m-d H:i:s P'));

        $dt = PlainDateTime::parse('2026-03-05T10:20:30')->toChronos();
        self::assertSame('2026-03-05 10:20:30 +00:00', $dt->format('Y-m-d H:i:s P'));

        $c = \Windwalker\Core\DateTime\Chronos::create('2026-01-01 00:00:00', 'Asia/Tokyo');
        self::assertSame($c, $c->toChronos());
        self::assertSame('UTC', $c->toChronos('UTC')->getTimezone()->getName());
    }

    #[Test]
    public function calendarFields(): void
    {
        $d = PlainDate::of(2024, 12, 30);

        self::assertSame([364, 1, 2025, 366, true], [
            $d->getDayOfYear(),
            $d->getWeekOfYear(),
            $d->getYearOfWeek(),
            $d->getDaysInYear(),
            $d->isInLeapYear(),
        ]);
        self::assertSame(['364', '1', '2025', '366', true], [
            $d->dayOfYear,
            $d->weekOfYear,
            $d->yearOfWeek,
            $d->daysInYear,
            $d->inLeapYear,
        ]);
        self::assertSame(1, PlainDate::of(2026, 1, 1)->getDayOfTheYear());
        self::assertSame(0, PlainDate::of(2026, 1, 1)->getDayOfYear());
        self::assertSame('59', PlainDateTime::of(2026, 3, 1, 12)->dayOfYear);
        self::assertSame('60', PlainDateTime::of(2026, 3, 1, 12)->dayOfTheYear);
        self::assertSame(365, PlainDate::of(2026, 6, 1)->getDaysInYear());
    }
}
