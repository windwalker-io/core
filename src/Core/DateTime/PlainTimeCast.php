<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateTimeInterface;
use Windwalker\ORM\Cast\CastInterface;

/**
 * Cast a TIME column to PlainTime and back. No time zone conversion is involved.
 *
 * Values outside 00:00:00 - 23:59:59 (e.g. MySQL "838:59:59") are rejected.
 */
class PlainTimeCast implements CastInterface
{
    public function hydrate(mixed $value): ?PlainTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof PlainTime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return PlainTime::from($value);
        }

        return PlainTime::parse((string) $value);
    }

    public function extract(mixed $value): ?string
    {
        return $this->hydrate($value)?->toIso();
    }
}
