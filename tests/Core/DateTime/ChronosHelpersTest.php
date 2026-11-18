<?php

declare(strict_types=1);

namespace Windwalker\Core\Test\DateTime;

use DateTime;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\Instant;
use TypeError;

use function Windwalker\chronos;
use function Windwalker\must_chronos;
use function Windwalker\to_chronos;

class ChronosHelpersTest extends TestCase
{
    public function testMustChronosParsesValidValue(): void
    {
        self::assertSame('2026-03-06', must_chronos('2026-03-06')->format('Y-m-d'));
    }

    public function testMustChronosThrowsOnInvalidValue(): void
    {
        $this->expectException(\Throwable::class);

        must_chronos('not-a-date');
    }

    public function testToChronosReturnsNullForNullAndEmpty(): void
    {
        self::assertNull(to_chronos(null));
        self::assertNull(to_chronos(''));
    }

    public function testToChronosParsesValidValue(): void
    {
        self::assertSame('2026-03-06', to_chronos('2026-03-06')->format('Y-m-d'));
    }

    public function testToChronosThrowsOnInvalidValue(): void
    {
        $this->expectException(\Throwable::class);

        to_chronos('not-a-date');
    }

    public function testDeprecatedChronosStillBehavesLikeMustChronos(): void
    {
        self::assertSame('2026-03-06', chronos('2026-03-06')->format('Y-m-d'));

        $this->expectException(\Throwable::class);

        chronos('not-a-date');
    }

    public function testComparePlainDateWithString(): void
    {
        $date = PlainDate::parse('2026-03-06');

        self::assertSame(0, ChronosService::compare($date, '2026-03-06'));
        self::assertTrue(ChronosService::compare($date, '2026-03-07') < 0);
    }

    public function testCompareInstantWithChronos(): void
    {
        $instant = Instant::parse('2026-03-06T10:00:00Z');
        $chronos = Chronos::wrap('2026-03-06T10:00:00Z');

        self::assertSame(0, ChronosService::compare($instant, $chronos));
    }

    public function testComparePlainDateWithNativeDateTimeThrows(): void
    {
        $this->expectException(TypeError::class);

        ChronosService::compare(PlainDate::parse('2026-03-06'), new DateTime('2026-03-06'));
    }
}
