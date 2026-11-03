<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Utilities;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Utilities\Base64Url;

class Base64UrlTest extends TestCase
{
    #[Test]
    public function encodeReplacesUnsafeCharsAndStripsPadding(): void
    {
        // Standard base64 of this binary is "++/++w==" (contains '+', '/' and padding).
        $data = hex2bin('fbeffefb');

        self::assertSame('--_--w', Base64Url::encode($data));
    }

    #[Test]
    public function encodeStripsPaddingForPlainText(): void
    {
        self::assertSame(
            'YW55IGNhcm5hbCBwbGVhc3VyZS4',
            Base64Url::encode('any carnal pleasure.')
        );
    }

    #[Test]
    public function decodeReversesEncodeForBinaryContainingUnsafeChars(): void
    {
        $data = hex2bin('fbeffefb');

        self::assertSame($data, Base64Url::decode(Base64Url::encode($data)));
    }

    #[Test]
    public function decodeReversesEncodeForPlainText(): void
    {
        $text = 'any carnal pleasure.';

        self::assertSame($text, Base64Url::decode(Base64Url::encode($text)));
    }

    #[Test]
    public function decodeReturnsFalseForInvalidBase64(): void
    {
        self::assertFalse(Base64Url::decode('not-valid-base64!!!'));
    }

    #[Test]
    public function decodeReturnsEmptyStringForEmptyInput(): void
    {
        self::assertSame('', Base64Url::decode(''));
    }
}
