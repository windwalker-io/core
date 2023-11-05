<?php

declare(strict_types=1);

namespace Windwalker\Core\Utilities;

/**
 * The Base64Url class.
 */
class Base64Url
{
    public static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function decode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
