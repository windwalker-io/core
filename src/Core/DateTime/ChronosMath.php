<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Time\Duration;

/**
 * Internal calendar helpers shared by Instant and Plain* value objects.
 *
 * @internal
 */
final class ChronosMath
{
    private const string RELATIVE_PATTERN = '/^\s*(?:[+-]?\s*\d+\s*(?:years?|months?|weeks?|fortnights?|days?|hours?|mins?|minutes?'
        . '|secs?|seconds?|msecs?|milliseconds?|usecs?|microseconds?)\s*(?:,|and)?\s*)+(?:ago)?\s*$/i';

    /**
     * Convert an interval to signed fields. Every unit carries its own sign, `invert` is resolved.
     *
     * @return  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}
     */
    public static function fields(Duration|DateInterval|string $interval): array
    {
        if (is_string($interval)) {
            $interval = self::parseString($interval);
        }

        if ($interval instanceof Duration) {
            $sign = $interval->negative ? -1 : 1;
            $seconds = $interval->seconds;
            $us = intdiv($interval->nanoseconds, 1000);

            return [
                'y' => 0,
                'm' => 0,
                'd' => 0,
                'h' => $sign * intdiv($seconds, 3600),
                'i' => $sign * intdiv($seconds % 3600, 60),
                's' => $sign * ($seconds % 60),
                'us' => $sign * ($us % 1_000_000),
            ];
        }

        $sign = $interval->invert ? -1 : 1;

        return [
            'y' => $sign * $interval->y,
            'm' => $sign * $interval->m,
            'd' => $sign * $interval->d,
            'h' => $sign * $interval->h,
            'i' => $sign * $interval->i,
            's' => $sign * $interval->s,
            'us' => $sign * (int) round($interval->f * 1_000_000),
        ];
    }

    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     *
     * @return  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}
     */
    public static function negate(array $fields): array
    {
        return array_map(static fn(int $v) => -$v, $fields);
    }

    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     * @param  list<string>                                                    $allowed
     */
    public static function assertOnly(array $fields, array $allowed, string $type): void
    {
        foreach ($fields as $unit => $value) {
            if ($value !== 0 && !in_array($unit, $allowed, true)) {
                throw new InvalidArgumentException(
                    sprintf('%s does not support the interval unit "%s".', $type, $unit)
                );
            }
        }
    }

    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     */
    public static function addTo(DateTimeImmutable $dt, array $fields, DateOverflow $overflow): DateTimeImmutable
    {
        if ($overflow === DateOverflow::Native) {
            return $dt->add(self::toInterval($fields));
        }

        $months = $fields['y'] * 12 + $fields['m'];

        if ($months !== 0) {
            [$year, $month, $day] = array_map('intval', explode('-', $dt->format('Y-n-j')));
            $index = $year * 12 + ($month - 1) + $months;
            $newYear = (int) floor($index / 12);
            $newMonth = $index - $newYear * 12 + 1;

            $dt = $dt->setDate($newYear, $newMonth, 1);
            $max = (int) $dt->format('t');

            if ($day > $max) {
                if ($overflow === DateOverflow::Reject) {
                    throw new \RangeException(
                        sprintf('Day %d does not exist in %04d-%02d.', $day, $newYear, $newMonth)
                    );
                }

                $day = $max;
            }

            $dt = $dt->setDate($newYear, $newMonth, $day);
        }

        $rest = ['y' => 0, 'm' => 0] + $fields;

        if (array_filter($rest) !== []) {
            $dt = $dt->add(self::toInterval($rest));
        }

        return $dt;
    }

    /**
     * @return  array{int, int, int}
     */
    public static function resolveDate(int $year, int $month, int $day, DateOverflow $overflow): array
    {
        if ($overflow === DateOverflow::Native) {
            return [$year, $month, $day];
        }

        $month = self::bound($month, 1, 12, $overflow, 'month');

        $max = (int) new DateTimeImmutable('2000-01-01')->setDate($year, $month, 1)->format('t');
        $day = self::bound($day, 1, $max, $overflow, 'day');

        return [$year, $month, $day];
    }

    /**
     * @return  array{int, int, int, int}
     */
    public static function resolveTime(int $hour, int $minute, int $second, int $us, DateOverflow $overflow): array
    {
        if ($overflow === DateOverflow::Native) {
            return [$hour, $minute, $second, $us];
        }

        return [
            self::bound($hour, 0, 23, $overflow, 'hour'),
            self::bound($minute, 0, 59, $overflow, 'minute'),
            self::bound($second, 0, 59, $overflow, 'second'),
            self::bound($us, 0, 999_999, $overflow, 'microsecond'),
        ];
    }

    private static function bound(int $value, int $min, int $max, DateOverflow $overflow, string $name): int
    {
        if ($value >= $min && $value <= $max) {
            return $value;
        }

        if ($overflow === DateOverflow::Reject) {
            throw new \RangeException(sprintf('%s %d is out of range %d-%d.', ucfirst($name), $value, $min, $max));
        }

        return max($min, min($max, $value));
    }

    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     */
    private static function toInterval(array $fields): DateInterval
    {
        $interval = new DateInterval('PT0S');
        $interval->y = $fields['y'];
        $interval->m = $fields['m'];
        $interval->d = $fields['d'];
        $interval->h = $fields['h'];
        $interval->i = $fields['i'];
        $interval->s = $fields['s'];
        $interval->f = $fields['us'] / 1_000_000;

        return $interval;
    }

    private static function parseString(string $value): DateInterval
    {
        $value = trim($value);

        if (str_starts_with($value, 'P')) {
            try {
                return new DateInterval($value);
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Invalid ISO 8601 duration "%s".', $value), 0, $e);
            }
        }

        if (!preg_match(self::RELATIVE_PATTERN, $value)) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not an additive interval, only "N unit" terms are supported.', $value)
            );
        }

        return DateInterval::createFromDateString($value)
            ?: throw new InvalidArgumentException(sprintf('Invalid interval string "%s".', $value));
    }
}
