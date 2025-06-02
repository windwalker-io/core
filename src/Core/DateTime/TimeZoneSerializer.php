<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Windwalker\ORM\Attributes\JsonSerializerInterface;

use function Windwalker\chronos;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TimeZoneSerializer implements JsonSerializerInterface
{
    public function __construct(public \DateTimeZone|string $to = 'UTC')
    {
    }

    public function serialize(mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        $tz = Chronos::wrapTimezoneObject($this->to);

        return chronos($data)->setTimezone($tz);
    }
}
