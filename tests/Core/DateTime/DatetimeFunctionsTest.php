<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateMalformedStringException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateTime;
use Windwalker\Core\DateTime\PlainTime;

use function Windwalker\must_plain_date;
use function Windwalker\must_plain_datetime;
use function Windwalker\must_plain_time;
use function Windwalker\to_instant;
use function Windwalker\to_plain_date;
use function Windwalker\to_plain_datetime;
use function Windwalker\to_plain_time;
use function Windwalker\try_chronos;
use function Windwalker\try_instant;
use function Windwalker\try_plain_date;
use function Windwalker\try_plain_datetime;
use function Windwalker\try_plain_time;

/**
 * Covers the must_*, to_* and try_* plain date/time helpers in datetime.functions.php.
 */
class DatetimeFunctionsTest extends TestCase
{
    #[Test]
    public function mustPlainDateAcceptsValidValues(): void
    {
        self::assertSame('2026-03-05', must_plain_date('2026-03-05')->toIso());
        self::assertSame('2026-03-05', must_plain_date('2026-03-05 10:20:30')->toIso());

        $date = PlainDate::of(2026, 3, 5);
        self::assertSame($date, must_plain_date($date));
        self::assertSame('2026-03-05', must_plain_date(Chronos::create('2026-03-05 03:00', 'Asia/Taipei'))->toIso());
    }

    #[Test]
    public function mustPlainDateRejectsInvalidValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        must_plain_date('2026/03/05');
    }

    #[Test]
    public function mustPlainDateRejectsOutOfRangeDate(): void
    {
        $this->expectException(DateMalformedStringException::class);

        must_plain_date('2026-02-30');
    }

    #[Test]
    public function mustPlainDateRejectsNull(): void
    {
        $this->expectException(TypeError::class);

        must_plain_date(null);
    }

    #[Test]
    public function mustPlainDateRejectsEmptyString(): void
    {
        $this->expectException(DateMalformedStringException::class);

        must_plain_date('');
    }

    #[Test]
    public function toPlainDateReturnsNullForNullAndEmptyString(): void
    {
        self::assertNull(to_plain_date(null));
        self::assertNull(to_plain_date(''));
    }

    #[Test]
    public function toPlainDateConvertsValidValues(): void
    {
        self::assertSame('2026-03-05', to_plain_date('2026-03-05')?->toIso());
    }

    #[Test]
    public function toPlainDateThrowsForInvalidValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        to_plain_date('not-a-date');
    }

    #[Test]
    public function tryPlainDateReturnsNullForInvalidFormatAndRange(): void
    {
        self::assertNull(try_plain_date('2026/03/05'));
        self::assertNull(try_plain_date('2026-02-30'));
        self::assertNull(try_plain_date('garbage'));
    }

    #[Test]
    public function tryPlainDateReturnsNullForNullEmptyAndUnsupportedTypes(): void
    {
        self::assertNull(try_plain_date(null));
        self::assertNull(try_plain_date(''));
        self::assertNull(try_plain_date(20260305));
        self::assertNull(try_plain_date([]));
    }

    #[Test]
    public function tryPlainDateConvertsValidValues(): void
    {
        self::assertSame('2026-03-05', try_plain_date('2026-03-05')?->toIso());
        self::assertSame('2026-03-05', try_plain_date('2026-03-05 10:20:30')?->toIso());
        self::assertSame('2026-03-05', try_plain_date(Chronos::create('2026-03-05 03:00', 'Asia/Taipei'))?->toIso());
    }

    #[Test]
    public function mustPlainTimeAcceptsValidValues(): void
    {
        self::assertSame('10:20:30', must_plain_time('10:20:30')->toIso());

        $time = PlainTime::parse('10:20:30');
        self::assertSame($time, must_plain_time($time));
    }

    #[Test]
    public function mustPlainTimeRejectsInvalidAndOutOfRangeValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        must_plain_time('25:00:00');
    }

    #[Test]
    public function mustPlainTimeRejectsNull(): void
    {
        $this->expectException(TypeError::class);

        must_plain_time(null);
    }

    #[Test]
    public function toPlainTimeReturnsNullForNullAndEmptyString(): void
    {
        self::assertNull(to_plain_time(null));
        self::assertNull(to_plain_time(''));
        self::assertSame('10:20:30', to_plain_time('10:20:30')?->toIso());
    }

    #[Test]
    public function toPlainTimeThrowsForInvalidValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        to_plain_time('25:00:00');
    }

    #[Test]
    public function tryPlainTimeReturnsNullForInvalidValues(): void
    {
        self::assertNull(try_plain_time('25:00:00'));
        self::assertNull(try_plain_time('nope'));
        self::assertNull(try_plain_time(null));
        self::assertNull(try_plain_time(''));
        self::assertNull(try_plain_time(1.5));
        self::assertSame('10:20:30', try_plain_time('10:20:30')?->toIso());
    }

    #[Test]
    public function mustPlainDateTimeAcceptsValidValues(): void
    {
        self::assertSame('2026-03-05T10:20:30', must_plain_datetime('2026-03-05 10:20:30')->toIso());

        $dateTime = PlainDateTime::parse('2026-03-05 10:20:30');
        self::assertSame($dateTime, must_plain_datetime($dateTime));
    }

    #[Test]
    public function mustPlainDateTimeRejectsInvalidValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        must_plain_datetime('2026-03-05 25:00:00');
    }

    #[Test]
    public function toPlainDateTimeReturnsNullForNullAndEmptyString(): void
    {
        self::assertNull(to_plain_datetime(null));
        self::assertNull(to_plain_datetime(''));
        self::assertSame(
            '2026-03-05T10:20:30',
            to_plain_datetime('2026-03-05 10:20:30')?->toIso()
        );
    }

    #[Test]
    public function toPlainDateTimeThrowsForInvalidValues(): void
    {
        $this->expectException(DateMalformedStringException::class);

        to_plain_datetime('2026-03-05 25:00:00');
    }

    #[Test]
    public function tryPlainDateTimeReturnsNullForInvalidValues(): void
    {
        self::assertNull(try_plain_datetime('2026-03-05 25:00:00'));
        self::assertNull(try_plain_datetime('2026-02-30 10:00:00'));
        self::assertNull(try_plain_datetime(null));
        self::assertNull(try_plain_datetime(''));
        self::assertNull(try_plain_datetime(true));
    }

    #[Test]
    public function tryPlainDateTimeConvertsValidValues(): void
    {
        self::assertSame(
            '2026-03-05T10:20:30',
            try_plain_datetime('2026-03-05 10:20:30')?->toIso()
        );
        self::assertSame(
            '2026-03-05T03:00:00',
            try_plain_datetime(Chronos::create('2026-03-05 03:00', 'Asia/Taipei'))?->toIso()
        );
    }

    #[Test]
    public function toInstantReturnsNullForNullAndEmpty(): void
    {
        self::assertNull(to_instant(null));
        self::assertNull(to_instant(''));
    }

    #[Test]
    public function toInstantThrowsOnInvalidString(): void
    {
        $this->expectException(DateMalformedStringException::class);

        to_instant('not-a-date');
    }

    #[Test]
    public function tryInstantReturnsNullForInvalidString(): void
    {
        self::assertNull(try_instant('not-a-date'));
        self::assertNull(try_instant(''));
        self::assertNull(try_instant(null));
        self::assertNull(try_instant("2026-03-05T02:00:00Z\0"));
        self::assertSame('2026-03-05T02:00:00Z', try_instant('2026-03-05T02:00:00Z')?->toIso());
    }

    #[Test]
    public function tryChronosReturnsNullForInvalidString(): void
    {
        self::assertNull(try_chronos('not-a-date'));
        self::assertNull(try_chronos(''));
        self::assertNull(try_chronos(null));
        self::assertSame('2026-03-05', try_chronos('2026-03-05')?->format('Y-m-d'));
    }
}
