<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateTimeInterface;
use Windwalker\ORM\Cast\CastInterface;

/**
 * Cast a DATE column to PlainDate and back. No time zone conversion is involved.
 */
class PlainDateCast implements CastInterface
{
    public function hydrate(mixed $value): ?PlainDate
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof PlainDate) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return PlainDate::from($value);
        }

        $value = (string) $value;

        // Some drivers return a full datetime for DATE columns (e.g. Oracle).
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})(?:[ T].*)?$/', $value, $matches)) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s", expect Y-m-d.', $value));
        }

        // MySQL zero date is not a real date.
        if ($matches[1] === '0000-00-00') {
            return null;
        }

        return PlainDate::parse($matches[1]);
    }

    public function extract(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof PlainDate) {
            return $value->toIso();
        }

        if ($value instanceof DateTimeInterface) {
            return PlainDate::from($value)->toIso();
        }

        return PlainDate::parse((string) $value)->toIso();
    }
}
