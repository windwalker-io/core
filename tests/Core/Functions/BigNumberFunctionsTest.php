<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Functions;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Windwalker\try_big_decimal;
use function Windwalker\try_big_integer;

/**
 * Covers try_big_integer() and try_big_decimal() in core functions.php.
 */
class BigNumberFunctionsTest extends TestCase
{
    #[Test]
    public function tryBigIntegerReturnsNullForNull(): void
    {
        self::assertNull(try_big_integer(null));
    }

    #[Test]
    public function tryBigIntegerConvertsNumericValues(): void
    {
        self::assertSame('12345678901234567890', (string) try_big_integer('12345678901234567890'));
        self::assertSame('42', (string) try_big_integer(42));
        self::assertSame('7', (string) try_big_integer('7'));
    }

    #[Test]
    public function tryBigIntegerPassesThroughInstance(): void
    {
        $value = BigInteger::of(9);

        self::assertSame($value, try_big_integer($value));
    }

    #[Test]
    public function tryBigIntegerThrowsOnInvalidString(): void
    {
        $this->expectException(NumberFormatException::class);

        try_big_integer('abc');
    }

    #[Test]
    public function tryBigDecimalReturnsNullForNull(): void
    {
        self::assertNull(try_big_decimal(null));
        self::assertNull(try_big_decimal(null, 2));
    }

    #[Test]
    public function tryBigDecimalUsesDefaultScaleOfFour(): void
    {
        self::assertSame('3.0000', (string) try_big_decimal('3'));
    }

    #[Test]
    public function tryBigDecimalAppliesScale(): void
    {
        self::assertSame('1.10', (string) try_big_decimal(1.1, 2));
        self::assertSame('0.50', (string) try_big_decimal('0.5', 2));
    }

    #[Test]
    #[DataProvider('roundingProvider')]
    public function tryBigDecimalRoundsHalfUpByDefault(string $input, int $scale, string $expected): void
    {
        self::assertSame($expected, (string) try_big_decimal($input, $scale));
    }

    public static function roundingProvider(): array
    {
        return [
            'positive half rounds up' => ['2.345', 2, '2.35'],
            'negative half rounds away from zero' => ['-2.5', 0, '-3'],
            'four digit scale' => ['1.23456', 4, '1.2346'],
        ];
    }

    #[Test]
    public function tryBigDecimalAcceptsExplicitRoundingMode(): void
    {
        self::assertSame('2.34', (string) try_big_decimal('2.345', 2, RoundingMode::Down));
    }

    #[Test]
    public function tryBigDecimalPassesThroughInstanceWithScale(): void
    {
        $value = BigDecimal::of('1.5');

        self::assertSame('1.50', (string) try_big_decimal($value, 2));
    }
}
