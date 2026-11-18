<?php

declare(strict_types=1);

namespace Windwalker {

    use DateTimeInterface;
    use DateTimeZone;
    use Exception;
    use Windwalker\Core\DateTime\Chronos;
    use Windwalker\Core\DateTime\ChronosService;
    use Windwalker\Core\DateTime\Contract\HasDateInterface;
    use Windwalker\Core\DateTime\Contract\HasInstantInterface;
    use Windwalker\Core\DateTime\Contract\HasTimeInterface;
    use Windwalker\Core\DateTime\Instant;
    use Windwalker\Core\DateTime\PlainDate;
    use Windwalker\Core\DateTime\PlainDateTime;
    use Windwalker\Core\DateTime\PlainTime;

    if (!function_exists('\Windwalker\must_chronos')) {
        /**
         * Strictly convert a value to Chronos. Invalid values throw an exception.
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos
         * @throws  \Throwable
         */
        function must_chronos(mixed $date = null, string|DateTimeZone|null $tz = null): Chronos
        {
            return Chronos::wrap($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\to_chronos')) {
        /**
         * Convert a value to Chronos, null and empty string return NULL.
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos|null
         * @throws  \Throwable
         */
        function to_chronos(mixed $date = null, string|DateTimeZone|null $tz = null): ?Chronos
        {
            return $date === null || $date === '' ? null : must_chronos($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\chronos')) {
        /**
         * chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos
         * @deprecated Use must_chronos() instead.
         */
        function chronos(mixed $date = null, string|DateTimeZone|null $tz = null): Chronos
        {
            return must_chronos($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\chronosOrNull')) {
        /**
         * chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos|null
         * @deprecated Use try_chronos()
         */
        function chronosOrNull(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?Chronos
        {
            return Chronos::wrapOrNull($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\try_chronos')) {
        /**
         * try_chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos|null
         */
        function try_chronos(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?Chronos
        {
            return Chronos::tryWrap($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\to_instant')) {
        /**
         * Strictly convert a value to Instant.
         *
         * Accepts: Instant (returned as is), any HasInstantInterface (Chronos), \DateTimeInterface,
         * and strict ISO 8601 string with time zone. PlainDate/PlainTime/PlainDateTime are rejected
         * because they have no time zone.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_instant(mixed $value): Instant
        {
            return match (true) {
                $value instanceof Instant => $value,
                $value instanceof HasInstantInterface => $value->toInstant(),
                $value instanceof DateTimeInterface => Instant::from($value),
                is_string($value) => Instant::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to Instant.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\try_instant')) {
        /**
         * Same as to_instant() but returns NULL for null or empty string.
         */
        function try_instant(mixed $value): ?Instant
        {
            return $value === null || $value === '' ? null : to_instant($value);
        }
    }

    if (!function_exists('\Windwalker\must_plain_date')) {
        /**
         * Strictly convert a value to PlainDate, using the wall-clock date without any time zone conversion.
         *
         * Accepts: PlainDate (returned as is), any HasDateInterface (PlainDateTime, Chronos),
         * \DateTimeInterface, and strict `Y-m-d` string. Instant is rejected. Null and empty string are rejected.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function must_plain_date(mixed $value): PlainDate
        {
            return match (true) {
                $value instanceof PlainDate => $value,
                $value instanceof HasDateInterface => $value->toPlainDate(),
                $value instanceof DateTimeInterface => PlainDate::from($value),
                is_string($value) => PlainDate::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to PlainDate.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\to_plain_date')) {
        /**
         * Same as must_plain_date() but returns NULL for null or empty string.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_date(mixed $value): ?PlainDate
        {
            return $value === null || $value === '' ? null : must_plain_date($value);
        }
    }

    if (!function_exists('\Windwalker\try_plain_date')) {
        /**
         * Same as to_plain_date() but returns NULL for invalid values instead of throwing.
         */
        function try_plain_date(mixed $value): ?PlainDate
        {
            if ($value instanceof PlainDate) {
                return $value;
            }

            if ($value instanceof HasDateInterface) {
                return $value->toPlainDate();
            }

            if ($value instanceof DateTimeInterface) {
                return PlainDate::from($value);
            }

            if (!is_string($value) || $value === '') {
                return null;
            }

            try {
                return PlainDate::parse($value);
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
    }

    if (!function_exists('\Windwalker\must_plain_time')) {
        /**
         * Strictly convert a value to PlainTime, using the wall-clock time without any time zone conversion.
         *
         * Accepts: PlainTime (returned as is), any HasTimeInterface (PlainDateTime, Chronos),
         * \DateTimeInterface, and strict time string. Instant is rejected. Null and empty string are rejected.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function must_plain_time(mixed $value): PlainTime
        {
            return match (true) {
                $value instanceof PlainTime => $value,
                $value instanceof HasTimeInterface => $value->toPlainTime(),
                $value instanceof DateTimeInterface => PlainTime::from($value),
                is_string($value) => PlainTime::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to PlainTime.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\to_plain_time')) {
        /**
         * Same as must_plain_time() but returns NULL for null or empty string.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_time(mixed $value): ?PlainTime
        {
            return $value === null || $value === '' ? null : must_plain_time($value);
        }
    }

    if (!function_exists('\Windwalker\try_plain_time')) {
        /**
         * Same as to_plain_time() but returns NULL for invalid values instead of throwing.
         */
        function try_plain_time(mixed $value): ?PlainTime
        {
            if ($value instanceof PlainTime) {
                return $value;
            }

            if ($value instanceof HasTimeInterface) {
                return $value->toPlainTime();
            }

            if ($value instanceof DateTimeInterface) {
                return PlainTime::from($value);
            }

            if (!is_string($value) || $value === '') {
                return null;
            }

            try {
                return PlainTime::parse($value);
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
    }

    if (!function_exists('\Windwalker\must_plain_datetime')) {
        /**
         * Strictly convert a value to PlainDateTime, using the wall-clock fields without any time zone conversion.
         *
         * Accepts: PlainDateTime (returned as is), Chronos, \DateTimeInterface, and strict ISO string
         * without time zone. PlainDate/PlainTime/Instant are rejected, combine or convert explicitly.
         * Null and empty string are rejected.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function must_plain_datetime(mixed $value): PlainDateTime
        {
            return match (true) {
                $value instanceof PlainDateTime => $value,
                $value instanceof Chronos => $value->toPlainDateTime(),
                $value instanceof DateTimeInterface => PlainDateTime::from($value),
                is_string($value) => PlainDateTime::parse($value),
                default => throw new \TypeError(
                    sprintf('Cannot convert %s to PlainDateTime.', get_debug_type($value))
                ),
            };
        }
    }

    if (!function_exists('\Windwalker\to_plain_datetime')) {
        /**
         * Same as must_plain_datetime() but returns NULL for null or empty string.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_datetime(mixed $value): ?PlainDateTime
        {
            return $value === null || $value === '' ? null : must_plain_datetime($value);
        }
    }

    if (!function_exists('\Windwalker\try_plain_datetime')) {
        /**
         * Same as to_plain_datetime() but returns NULL for invalid values instead of throwing.
         */
        function try_plain_datetime(mixed $value): ?PlainDateTime
        {
            if ($value instanceof PlainDateTime) {
                return $value;
            }

            if ($value instanceof Chronos) {
                return $value->toPlainDateTime();
            }

            if ($value instanceof DateTimeInterface) {
                return PlainDateTime::from($value);
            }

            if (!is_string($value) || $value === '') {
                return null;
            }

            try {
                return PlainDateTime::parse($value);
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
    }

    if (!function_exists('\Windwalker\now')) {
        /**
         * chronos
         *
         * @param  string                    $format
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos
         *
         * @throws  Exception
         *
         * @deprecated  Use chronos('now', $tz)->format($format) instead.
         */
        function now(string $format, string|DateTimeZone|null $tz = null): string
        {
            return Chronos::now($format, $tz);
        }
    }

    if (!function_exists('\Windwalker\date_compare')) {
        /**
         * Compare date with another date. All dates will be compared as UTC timezone.
         *
         * ```php
         * date_compare(
         *     'now', // This will be UTC timezone
         *     $date2, // Another date with timezone 'Asia/Tokyo'
         * );
         * ```
         *
         * If you want to compare UTC with local time, use this.
         *
         * ```php
         * date_compare(
         *     chronos('now', 'Asia/Tokyo'),
         *     $date2, // Another date with timezone 'Asia/Tokyo'
         * );
         * ```
         *
         * @param  string|DateTimeInterface  $date1
         * @param  string|DateTimeInterface  $date2
         * @param  string|null               $operator
         *
         * @return  bool|int
         *
         * @throws  Exception
         */
        function date_compare(
            string|DateTimeInterface $date1,
            string|DateTimeInterface $date2,
            ?string $operator = null
        ): bool|int {
            return ChronosService::compare($date1, $date2, $operator);
        }
    }
}
