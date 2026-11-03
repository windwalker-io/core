<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Crypt;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Crypt\PseudoCrypt;

class PseudoCryptTest extends TestCase
{
    #[Test]
    public function base62EncodesZeroAsEmptyString(): void
    {
        self::assertSame('', PseudoCrypt::base62(0));
    }

    #[Test]
    public function base62AndUnbase62RoundTrip(): void
    {
        foreach ([1, 9, 61, 62, 123, 99999, '123456789012345'] as $value) {
            $encoded = PseudoCrypt::base62($value);
            self::assertSame((string) $value, (string) PseudoCrypt::unbase62($encoded));
        }
    }

    #[Test]
    public function base62UsesExpectedAlphabet(): void
    {
        // 61 is the last digit of chars62 (ascii 122 = 'z')
        self::assertSame('z', PseudoCrypt::base62(61));
        // 10 is the first letter (ascii 65 = 'A')
        self::assertSame('A', PseudoCrypt::base62(10));
        // 62 wraps around to '10'
        self::assertSame('10', PseudoCrypt::base62(62));
    }

    #[Test]
    public function hashReturnsStringPaddedToRequestedLength(): void
    {
        $crypt = new PseudoCrypt();

        $hash = $crypt->hash(1, 5);

        self::assertSame(5, strlen($hash));
    }

    #[Test]
    public function hashAndUnhashRoundTripForVariousLengthsAndNumbers(): void
    {
        // Each number must stay below 62^len (the smallest ceil here is 62^3 = 238328)
        // so the hash doesn't need more digits than the requested length.
        $crypt = new PseudoCrypt();

        foreach ([3, 5, 7] as $len) {
            foreach ([0, 1, 42, 1000, 99999] as $num) {
                $hash = $crypt->hash($num, $len);
                self::assertSame((string) $num, (string) $crypt->unhash($hash));
            }
        }
    }

    #[Test]
    public function nonZeroOffsetChangesHashOutput(): void
    {
        // A non-zero offset is only meaningful as a seed to vary the output; the
        // modular arithmetic does not invert it back to the original number, so
        // unhash() with offset != 0 is not expected to reproduce the input here.
        $plain = new PseudoCrypt();
        $offset = new PseudoCrypt(1000);

        self::assertNotSame($plain->hash(123, 5), $offset->hash(123, 5));
    }

    #[Test]
    public function getOffsetReturnsConstructorValue(): void
    {
        self::assertSame(0, new PseudoCrypt()->getOffset());
        self::assertSame(42, new PseudoCrypt(42)->getOffset());
    }

    #[Test]
    public function setOffsetUpdatesOffsetAndReturnsSelf(): void
    {
        $crypt = new PseudoCrypt();

        $returned = $crypt->setOffset(99);

        self::assertSame($crypt, $returned);
        self::assertSame(99, $crypt->getOffset());
    }
}
