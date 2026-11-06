<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Time\Duration;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\Clock;
use Windwalker\Core\DateTime\MockClock;

#[CoversClass(Chronos::class)]
class ChronosTest extends TestCase
{
    #[After]
    protected function resetClock(): void
    {
        Clock::reset();
    }

    #[Test]
    public function wrapReturnsSameInstanceWhenAlreadyChronos(): void
    {
        $chronos = new Chronos('2020-01-01');

        self::assertSame($chronos, Chronos::wrap($chronos));
    }

    #[Test]
    public function wrapConvertsDateTimeInterfaceByInterface(): void
    {
        $date = new DateTimeImmutable('2020-06-15 10:00:00', new DateTimeZone('UTC'));

        $result = Chronos::wrap($date);

        self::assertInstanceOf(Chronos::class, $result);
        self::assertSame('2020-06-15 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function withTimezoneKeepsInstantAndWithTimezoneKeepingLocalKeepsWallClock(): void
    {
        $taipei = Chronos::create('2026-03-05 10:00:00.123456', 'Asia/Taipei');

        $converted = $taipei->withTimezone('UTC');
        self::assertSame('2026-03-05 02:00:00.123456', $converted->format('Y-m-d H:i:s.u'));
        self::assertSame($taipei->getTimestamp(), $converted->getTimestamp());

        $moved = $taipei->withTimezoneKeepingLocal('UTC');
        self::assertSame('2026-03-05 10:00:00.123456', $moved->format('Y-m-d H:i:s.u'));
        self::assertSame('UTC', $moved->getTimezone()->getName());
        self::assertSame($taipei->getTimestamp() + 8 * 3600, $moved->getTimestamp());
        self::assertSame('2026-03-05 10:00:00', $taipei->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function wrapConvertsModernObjectsInUtcByDefault(): void
    {
        self::assertSame(
            '2026-03-05 00:00:00 +00:00',
            Chronos::wrap(\Windwalker\Core\DateTime\PlainDate::of(2026, 3, 5))->format('Y-m-d H:i:s P')
        );
        self::assertSame(
            '2026-03-05 00:00:00 +08:00',
            Chronos::wrap(\Windwalker\Core\DateTime\PlainDate::of(2026, 3, 5), 'Asia/Taipei')->format('Y-m-d H:i:s P')
        );
        self::assertSame(
            '2026-01-01 00:00:00 +00:00',
            Chronos::tryWrap(\Windwalker\Core\DateTime\Instant::parse('2026-01-01T00:00:00Z'))->format('Y-m-d H:i:s P')
        );
    }

    #[Test]
    public function wrapCreatesFromStringWhenNotDateTime(): void
    {
        $result = Chronos::wrap('2020-01-02 03:04:05');

        self::assertSame('2020-01-02 03:04:05', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function tryWrapReturnsNullForNullOrEmptyString(): void
    {
        self::assertNull(Chronos::tryWrap(null));
        self::assertNull(Chronos::tryWrap(''));
    }

    #[Test]
    public function tryWrapWrapsNonEmptyValue(): void
    {
        $result = Chronos::tryWrap('2020-01-02');

        self::assertInstanceOf(Chronos::class, $result);
        self::assertSame('2020-01-02', $result->format('Y-m-d'));
    }

    #[Test]
    public function wrapOrNullDelegatesToTryWrap(): void
    {
        self::assertNull(Chronos::wrapOrNull(null));

        $result = Chronos::wrapOrNull('2020-01-02');

        self::assertInstanceOf(Chronos::class, $result);
        self::assertSame('2020-01-02', $result->format('Y-m-d'));
    }

    #[Test]
    public function nowFormatsClockCurrentTime(): void
    {
        Clock::set(new MockClock(new Chronos('2020-06-15 12:00:00', 'UTC')));

        self::assertSame('2020-06-15 12:00:00', Chronos::now());
        self::assertSame('2020-06-15', Chronos::now('Y-m-d'));
    }

    #[Test]
    public function constructorAcceptsNullAndUsesClockNow(): void
    {
        Clock::set('2021-05-06 07:08:09');

        $chronos = new Chronos();

        self::assertSame('2021-05-06 07:08:09', $chronos->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function constructorAcceptsClockInterface(): void
    {
        $clock = new MockClock('2021-07-08 09:10:11');

        $chronos = new Chronos($clock);

        self::assertSame('2021-07-08 09:10:11', $chronos->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function constructorAcceptsNumericTimestamp(): void
    {
        $timestamp = 1000000000;

        $chronos = new Chronos($timestamp);

        self::assertSame($timestamp, $chronos->getTimestamp());
    }

    #[Test]
    public function constructorAcceptsDateTimeInterfaceAndInheritsTimezone(): void
    {
        $date = new DateTime('2020-03-04 05:06:07', new DateTimeZone('Asia/Tokyo'));

        $chronos = new Chronos($date);

        self::assertSame('2020-03-04 05:06:07', $chronos->format('Y-m-d H:i:s'));
        self::assertSame('Asia/Tokyo', $chronos->getTimezone()->getName());
    }

    #[Test]
    public function constructorAcceptsStringTimezone(): void
    {
        $chronos = new Chronos('2020-01-01', 'Asia/Taipei');

        self::assertSame('Asia/Taipei', $chronos->getTimezone()->getName());
    }

    #[Test]
    public function wrapTimezoneObjectConvertsStringToDateTimeZone(): void
    {
        $tz = Chronos::wrapTimezoneObject('UTC');

        self::assertInstanceOf(DateTimeZone::class, $tz);
        self::assertSame('UTC', $tz->getName());
    }

    #[Test]
    public function wrapTimezoneObjectReturnsNullForNull(): void
    {
        self::assertNull(Chronos::wrapTimezoneObject(null));
    }

    #[Test]
    public function wrapTimezoneObjectPassesThroughDateTimeZone(): void
    {
        $tz = new DateTimeZone('UTC');

        self::assertSame($tz, Chronos::wrapTimezoneObject($tz));
    }

    #[Test]
    public function toFormatAcceptsStringDate(): void
    {
        self::assertSame('2020-01-02', Chronos::toFormat('2020-01-02 03:04:05', 'Y-m-d'));
    }

    #[Test]
    public function toFormatAcceptsDateTimeInterface(): void
    {
        $date = new DateTimeImmutable('2020-01-02 03:04:05');

        self::assertSame('2020-01-02', Chronos::toFormat($date, 'Y-m-d'));
    }

    #[Test]
    public function createFromFormatParsesTimeCorrectly(): void
    {
        $chronos = Chronos::createFromFormat('Y-m-d H:i:s', '2020-02-03 04:05:06');

        self::assertInstanceOf(Chronos::class, $chronos);
        self::assertSame('2020-02-03 04:05:06', $chronos->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createFromFormatReturnsFalseOnInvalidInput(): void
    {
        $result = Chronos::createFromFormat('Y-m-d', 'not-a-date');

        self::assertFalse($result);
    }

    #[Test]
    public function createIntervalReturnsSameDateIntervalInstance(): void
    {
        $interval = new DateInterval('PT30S');

        self::assertSame($interval, Chronos::createInterval($interval));
    }

    #[Test]
    public function createIntervalConvertsIntToSecondsFormat(): void
    {
        $interval = Chronos::createInterval(90);

        self::assertSame(90, Chronos::intervalToSeconds($interval));
    }

    #[Test]
    public function createIntervalParsesIso8601String(): void
    {
        $interval = Chronos::createInterval('PT1H');

        self::assertSame(3600, Chronos::intervalToSeconds($interval));
    }

    #[Test]
    public function createIntervalParsesRelativeDateString(): void
    {
        $interval = Chronos::createInterval('1 day');

        self::assertSame(86400, Chronos::intervalToSeconds($interval));
    }

    #[Test]
    public function intervalToSecondsAcceptsStringInterval(): void
    {
        self::assertSame(60, Chronos::intervalToSeconds('PT1M'));
    }

    #[Test]
    public function wrapDurationConvertsDateIntervalToDuration(): void
    {
        $duration = Chronos::wrapDuration(new DateInterval('PT30S'));

        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame(30, $duration->seconds);
    }

    #[Test]
    public function wrapDurationConvertsIntToDuration(): void
    {
        $duration = Chronos::wrapDuration(45);

        self::assertSame(45, $duration->seconds);
    }

    #[Test]
    public function wrapDurationConvertsIso8601StringToDuration(): void
    {
        $duration = Chronos::wrapDuration('PT1H');

        self::assertSame(3600, $duration->seconds);
    }

    #[Test]
    public function wrapDurationConvertsRelativeStringToDuration(): void
    {
        $duration = Chronos::wrapDuration('1 minute');

        self::assertSame(60, $duration->seconds);
    }

    #[Test]
    public function wrapDurationReturnsSameDurationInstance(): void
    {
        $duration = Duration::fromSeconds(10);

        self::assertSame($duration, Chronos::wrapDuration($duration));
    }

    #[Test]
    public function tryDurationReturnsNullForNull(): void
    {
        self::assertNull(Chronos::tryDuration(null));
    }

    #[Test]
    public function tryDurationReturnsDurationForValidInput(): void
    {
        $duration = Chronos::tryDuration(15);

        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame(15, $duration->seconds);
    }

    #[Test]
    public function createPeriodBetweenBuildsDatePeriod(): void
    {
        $period = Chronos::createPeriodBetween('2020-01-01', 'P1D', '2020-01-04');

        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        self::assertSame(['2020-01-01', '2020-01-02', '2020-01-03'], $dates);
    }

    #[Test]
    public function createPeriodRecurrencesBuildsDatePeriod(): void
    {
        $period = Chronos::createPeriodRecurrences('2020-01-01', 'P1D', 2);

        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        self::assertSame(['2020-01-01', '2020-01-02', '2020-01-03'], $dates);
    }

    #[Test]
    public function formatWithoutTimezoneUsesParentFormat(): void
    {
        $chronos = new Chronos('2020-01-01 10:00:00', 'UTC');

        self::assertSame('2020-01-01 10:00:00', $chronos->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function formatWithTimezoneConvertsBeforeFormatting(): void
    {
        $chronos = new Chronos('2020-01-01 00:00:00', 'UTC');

        $formatted = $chronos->format('Y-m-d H:i:s', 'Asia/Taipei');

        self::assertSame('2020-01-01 08:00:00', $formatted);
        // Original instance must stay unaffected (format() uses a clone internally).
        self::assertSame('UTC', $chronos->getTimezone()->getName());
    }

    #[Test]
    public function isGtComparesAgainstGivenDate(): void
    {
        $chronos = new Chronos('2020-06-01');

        self::assertTrue($chronos->isGt('2020-01-01'));
        self::assertFalse($chronos->isGt('2020-12-01'));
    }

    #[Test]
    public function isGteComparesAgainstGivenDate(): void
    {
        $chronos = new Chronos('2020-06-01');

        self::assertTrue($chronos->isGte('2020-06-01'));
        self::assertTrue($chronos->isGte('2020-01-01'));
        self::assertFalse($chronos->isGte('2020-12-01'));
    }

    #[Test]
    public function isLtComparesAgainstGivenDate(): void
    {
        $chronos = new Chronos('2020-06-01');

        self::assertTrue($chronos->isLt('2020-12-01'));
        self::assertFalse($chronos->isLt('2020-01-01'));
    }

    #[Test]
    public function isLteComparesAgainstGivenDate(): void
    {
        $chronos = new Chronos('2020-06-01');

        self::assertTrue($chronos->isLte('2020-06-01'));
        self::assertTrue($chronos->isLte('2020-12-01'));
        self::assertFalse($chronos->isLte('2020-01-01'));
    }

    #[Test]
    public function isCurrentWithoutRangeComparesToExactSecond(): void
    {
        $chronos = new Chronos(time());

        self::assertTrue($chronos->isCurrent());

        $past = new Chronos(time() - 100);

        self::assertFalse($past->isCurrent());
    }

    #[Test]
    public function isCurrentWithRangeAllowsToleranceWindow(): void
    {
        $chronos = new Chronos(time() - 10);

        self::assertTrue($chronos->isCurrent('30 seconds'));
        self::assertFalse($chronos->isCurrent('5 seconds'));
    }

    #[Test]
    public function getterReturnsExpectedValuesForKnownProperties(): void
    {
        $chronos = new Chronos('2020-02-03 04:05:06', 'UTC');

        self::assertSame('29', $chronos->daysInMonth);
        self::assertSame('1', $chronos->dayOfWeek);
        self::assertSame('33', $chronos->dayOfYear);
        self::assertTrue($chronos->isLeapYear);
        self::assertSame('03', $chronos->day);
        self::assertSame('04', $chronos->hour);
        self::assertSame('05', $chronos->minute);
        self::assertSame('06', $chronos->second);
        self::assertSame('02', $chronos->month);
        self::assertSame('rd', $chronos->ordinal);
        self::assertSame('2020', $chronos->year);
        self::assertIsString($chronos->week);
    }

    #[Test]
    public function toStringUsesStaticFormat(): void
    {
        $previous = Chronos::$format;
        Chronos::$format = 'Y-m-d';

        try {
            $chronos = new Chronos('2020-01-02 03:04:05');

            self::assertSame('2020-01-02', (string) $chronos);
        } finally {
            Chronos::$format = $previous;
        }
    }

    #[Test]
    public function getOffsetFromGmtReturnsSecondsByDefault(): void
    {
        $chronos = new Chronos('2020-01-01', 'Asia/Taipei');

        self::assertSame(8 * 3600.0, $chronos->getOffsetFromGmt());
    }

    #[Test]
    public function getOffsetFromGmtReturnsHoursWhenRequested(): void
    {
        $chronos = new Chronos('2020-01-01', 'Asia/Taipei');

        self::assertSame(8.0, $chronos->getOffsetFromGmt(true));
    }

    #[Test]
    public function setTimezoneAcceptsString(): void
    {
        $chronos = new Chronos('2020-01-01', 'UTC');

        $result = $chronos->setTimezone('Asia/Taipei');

        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
    }

    #[Test]
    public function setTimezoneAcceptsDateTimeZoneInstance(): void
    {
        $chronos = new Chronos('2020-01-01', 'UTC');
        $tz = new DateTimeZone('Asia/Tokyo');

        $result = $chronos->setTimezone($tz);

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
    }

    #[Test]
    public function toISO8601FormatsWithoutMicroseconds(): void
    {
        $chronos = new Chronos('2020-01-02 03:04:05', 'UTC');

        self::assertSame('2020-01-02T03:04:05+00:00', $chronos->toISO8601());
    }

    #[Test]
    public function toRFC822FormatsDate(): void
    {
        $chronos = new Chronos('2020-01-02 03:04:05', 'UTC');

        self::assertSame('Thu, 02 Jan 2020 03:04:05 +0000', $chronos->toRFC822());
    }

    #[Test]
    public function toUnixReturnsIntegerTimestampByDefault(): void
    {
        $chronos = new Chronos('@1000000000');

        self::assertSame(1000000000, $chronos->toUnix());
    }

    #[Test]
    public function toUnixReturnsFloatWhenMicroRequested(): void
    {
        $chronos = new Chronos('@1000000000');

        self::assertSame(1000000000.0, $chronos->toUnix(true));
    }

    #[Test]
    public function fromClockCreatesChronosFromClockInterface(): void
    {
        $clock = new MockClock('2021-01-02 03:04:05');

        $chronos = Chronos::fromClock($clock);

        self::assertSame('2021-01-02 03:04:05', $chronos->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function jsonSerializeReturnsISO8601WithMicroseconds(): void
    {
        $chronos = new Chronos('2020-01-02 03:04:05.123456', 'UTC');

        self::assertSame($chronos->toISO8601(true), $chronos->jsonSerialize());
        self::assertStringContainsString('2020-01-02T03:04:05', $chronos->jsonSerialize());
    }

    #[Test]
    public function setClockDelegatesToClock(): void
    {
        Chronos::setClock('2021-09-10 11:12:13');

        self::assertSame('2021-09-10 11:12:13', Clock::now()->format('Y-m-d H:i:s'));
    }
}
