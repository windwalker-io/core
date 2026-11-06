<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RoundingMode;
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
     * Lenient ISO 8601 reader shared by the Plain types, like Temporal: the date part is required,
     * `T`/`t`/space separators, up to 9 fraction digits (truncated to microseconds) and a UTC offset
     * are accepted, and the offset is ignored. A `Z` designator is rejected because it means an instant.
     *
     * @return  array{year: int, month: int, day: int, hour: int, minute: int, second: int, microsecond: int, hasTime: bool}|null
     */
    public static function parseIso(string $value): ?array
    {
        $pattern = '/^(?<y>-?\d{4,6})-(?<mo>\d{2})-(?<d>\d{2})'
            . '(?:[Tt ](?<h>\d{2}):(?<i>\d{2})(?::(?<s>\d{2})(?:[.,](?<f>\d{1,9}))?)?'
            . '(?:[+-]\d{2}(?::?\d{2})?)?)?$/';

        if (!preg_match($pattern, $value, $m)) {
            return null;
        }

        $hasTime = ($m['h'] ?? '') !== '';

        try {
            self::resolveDate((int) $m['y'], (int) $m['mo'], (int) $m['d'], DateOverflow::Reject);

            if ($hasTime) {
                self::resolveTime(
                    (int) $m['h'],
                    (int) $m['i'],
                    (int) ($m['s'] ?? 0),
                    self::fractionToMicros($m['f'] ?? ''),
                    DateOverflow::Reject
                );
            }
        } catch (\RangeException) {
            return null;
        }

        return [
            'year' => (int) $m['y'],
            'month' => (int) $m['mo'],
            'day' => (int) $m['d'],
            'hour' => (int) ($m['h'] ?? 0),
            'minute' => (int) ($m['i'] ?? 0),
            'second' => (int) ($m['s'] ?? 0),
            'microsecond' => self::fractionToMicros($m['f'] ?? ''),
            'hasTime' => $hasTime,
        ];
    }

    /**
     * Time-of-day reader: `H:i`, `H:i:s`, `H:i:s.f` (up to 9 digits), optional leading `T`,
     * or any string accepted by parseIso() that has a time part.
     *
     * @return  array{int, int, int, int}|null  hour, minute, second, microsecond
     */
    public static function parseIsoTime(string $value): ?array
    {
        if (preg_match('/^[Tt]?(\d{2}):(\d{2})(?::(\d{2})(?:[.,](\d{1,9}))?)?$/', $value, $m)) {
            $parts = [(int) $m[1], (int) $m[2], (int) ($m[3] ?? 0), self::fractionToMicros($m[4] ?? '')];

            try {
                self::resolveTime(...[...$parts, DateOverflow::Reject]);
            } catch (\RangeException) {
                return null;
            }

            return $parts;
        }

        $parsed = self::parseIso($value);

        return $parsed !== null && $parsed['hasTime']
            ? [$parsed['hour'], $parsed['minute'], $parsed['second'], $parsed['microsecond']]
            : null;
    }

    private static function fractionToMicros(string $fraction): int
    {
        return (int) substr(str_pad($fraction, 6, '0'), 0, 6);
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

    public static function toMicros(DateTimeImmutable $dt): int
    {
        return $dt->getTimestamp() * 1_000_000 + (int) $dt->format('u');
    }

    public static function fromMicros(int $us): DateTimeImmutable
    {
        $seconds = intdiv($us, 1_000_000);
        $micro = $us - $seconds * 1_000_000;

        if ($micro < 0) {
            $seconds--;
            $micro += 1_000_000;
        }

        // The "u" format reads digits as a fraction, so it must be zero-padded to six digits.
        return DateTimeImmutable::createFromFormat('U u', sprintf('%d %06d', $seconds, $micro))
            ->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Divide and round to an integer. $d must be positive.
     */
    public static function roundDiv(int $n, int $d, RoundingMode $mode): int
    {
        $q = intdiv($n, $d);
        $r = $n - $q * $d;

        if ($r < 0) {
            $q--;
            $r += $d;
        }

        if ($r === 0) {
            return $q;
        }

        $negative = $n < 0;
        $half = $r * 2 <=> $d;

        return match ($mode) {
            RoundingMode::NegativeInfinity => $q,
            RoundingMode::PositiveInfinity => $q + 1,
            RoundingMode::TowardsZero => $negative ? $q + 1 : $q,
            RoundingMode::AwayFromZero => $negative ? $q : $q + 1,
            default => match (true) {
                $half < 0 => $q,
                $half > 0 => $q + 1,
                $mode === RoundingMode::HalfAwayFromZero => $negative ? $q : $q + 1,
                $mode === RoundingMode::HalfTowardsZero => $negative ? $q + 1 : $q,
                $mode === RoundingMode::HalfEven => ($q & 1) === 0 ? $q : $q + 1,
                default => ($q & 1) === 1 ? $q : $q + 1,
            },
        };
    }

    /**
     * Round a microsecond count to a multiple of $unit * $increment. $unit must be Day or smaller.
     */
    public static function roundMicros(int $us, DateTimeUnit $unit, int $increment, RoundingMode $mode): int
    {
        self::assertIncrement($increment);

        $step = $unit->microseconds() * $increment;

        return self::roundDiv($us, $step, $mode) * $step;
    }

    public static function assertIncrement(int $increment): void
    {
        if ($increment < 1) {
            throw new InvalidArgumentException('Rounding increment must be a positive integer.');
        }
    }

    /**
     * Validate a unit against the range the type supports. Returns the resolved unit.
     */
    public static function assertUnit(DateTimeUnit|string $unit, DateTimeUnit $max, DateTimeUnit $min): DateTimeUnit
    {
        $unit = DateTimeUnit::resolve($unit);

        if ($unit->isLargerThan($max) || $min->isLargerThan($unit)) {
            throw new InvalidArgumentException(
                sprintf('Unit "%s" is not supported, expect "%s" to "%s".', $unit->value, $max->value, $min->value)
            );
        }

        return $unit;
    }

    /**
     * Like Temporal, the default largest unit is raised to the smallest unit if it is smaller.
     *
     * @return  array{DateTimeUnit, DateTimeUnit}  Largest and smallest.
     */
    public static function resolveUnits(
        DateTimeUnit|string|null $largest,
        DateTimeUnit|string|null $smallest,
        DateTimeUnit $defaultLargest,
        DateTimeUnit $defaultSmallest,
        DateTimeUnit $max,
        DateTimeUnit $min,
    ): array {
        $smallest = $smallest === null ? $defaultSmallest : self::assertUnit($smallest, $max, $min);

        $largest = $largest === null
            ? ($defaultLargest->isLargerThan($smallest) ? $defaultLargest : $smallest)
            : self::assertUnit($largest, $max, $min);

        if ($smallest->isLargerThan($largest)) {
            throw new InvalidArgumentException(
                sprintf('Largest unit "%s" must not be smaller than smallest unit "%s".', $largest->value, $smallest->value)
            );
        }

        return [$largest, $smallest];
    }

    /**
     * The difference from $from to $to. Both must be naive wall-clock values in UTC.
     */
    public static function difference(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        DateTimeUnit|string|null $largest,
        DateTimeUnit|string|null $smallest,
        int $increment,
        RoundingMode $mode,
        DateTimeUnit $defaultLargest,
        DateTimeUnit $defaultSmallest,
        DateTimeUnit $max,
        DateTimeUnit $min,
    ): DateInterval {
        [$largest, $smallest] = self::resolveUnits($largest, $smallest, $defaultLargest, $defaultSmallest, $max, $min);

        self::assertIncrement($increment);

        if ($largest->rank() <= DateTimeUnit::Month->rank()) {
            return self::calendarDifference($from, $to, $largest, $smallest, $increment, $mode);
        }

        $us = self::roundMicros(self::toMicros($to) - self::toMicros($from), $smallest, $increment, $mode);

        return self::buildInterval(0, $us, $us < 0, $largest);
    }

    /**
     * The sign flips and Ceil/Floor swap, so that since() is the mirror image of until().
     */
    public static function mirrorMode(RoundingMode $mode): RoundingMode
    {
        return match ($mode) {
            RoundingMode::PositiveInfinity => RoundingMode::NegativeInfinity,
            RoundingMode::NegativeInfinity => RoundingMode::PositiveInfinity,
            default => $mode,
        };
    }

    public static function negateInterval(DateInterval $interval): DateInterval
    {
        $interval->invert = $interval->invert === 1 ? 0 : (int) self::isNonZero($interval);

        return $interval;
    }

    private static function isNonZero(DateInterval $interval): bool
    {
        return $interval->y || $interval->m || $interval->d || $interval->h || $interval->i || $interval->s
            || $interval->f;
    }

    /**
     * Years and months are counted by month-index like Temporal: 2026-01-31 until 2026-03-01 is
     * 1 month 1 day, and until 2026-02-28 is 28 days since February 28th is before the 31st.
     */
    private static function calendarDifference(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        DateTimeUnit $largest,
        DateTimeUnit $smallest,
        int $increment,
        RoundingMode $mode,
    ): DateInterval {
        $fromUs = self::toMicros($from);
        $toUs = self::toMicros($to);

        if ($fromUs === $toUs) {
            return self::buildInterval(0, 0, false, $largest);
        }

        $sign = $toUs > $fromUs ? 1 : -1;

        $months = ((int) $to->format('Y') - (int) $from->format('Y')) * 12
            + ((int) $to->format('n') - (int) $from->format('n'));

        $fromTail = self::monthTail($from);
        $toTail = self::monthTail($to);

        if ($sign > 0 && $toTail < $fromTail) {
            $months--;
        } elseif ($sign < 0 && $toTail > $fromTail) {
            $months++;
        }

        if ($smallest === DateTimeUnit::Year || $smallest === DateTimeUnit::Month) {
            $unit = ($smallest === DateTimeUnit::Year ? 12 : 1) * $increment;
            $whole = intdiv($months, $unit);
            $startUs = self::toMicros(self::addMonths($from, $whole * $unit));
            $endUs = self::toMicros(self::addMonths($from, ($whole + $sign) * $unit));
            $den = abs($endUs - $startUs);
            $num = abs($toUs - $startUs);

            $rounded = self::roundDiv($sign * (abs($whole) * $den + $num), $den, $mode) * $unit;

            return self::buildInterval($rounded, 0, $rounded < 0, $largest);
        }

        $inter = self::addMonths($from, $months);
        $rest = $toUs - self::toMicros($inter);
        $rounded = self::roundMicros($rest, $smallest, $increment, $mode);

        if ($rounded !== $rest) {
            // Re-measure to the rounded end point so that a carry is rebalanced into larger units.
            return self::calendarDifference(
                $from,
                self::fromMicros(self::toMicros($inter) + $rounded),
                $largest,
                DateTimeUnit::Microsecond,
                1,
                $mode
            );
        }

        return self::buildInterval($months, $rest, $sign < 0, $largest);
    }

    private static function monthTail(DateTimeImmutable $dt): int
    {
        [$day, $hour, $minute, $second, $us] = array_map('intval', explode(' ', $dt->format('j G i s u')));

        return $day * 86_400_000_000 + $hour * 3_600_000_000 + $minute * 60_000_000 + $second * 1_000_000 + $us;
    }

    private static function addMonths(DateTimeImmutable $dt, int $months): DateTimeImmutable
    {
        return self::addTo(
            $dt,
            ['y' => 0, 'm' => $months, 'd' => 0, 'h' => 0, 'i' => 0, 's' => 0, 'us' => 0],
            DateOverflow::Constrain
        );
    }

    /**
     * @param  int           $months   Only its absolute value is used.
     * @param  int           $us       Only its absolute value is used.
     * @param  DateTimeUnit  $largest  The unit that absorbs everything above it: with Hour, 30 hours stay 30 hours.
     */
    private static function buildInterval(
        int $months,
        int $us,
        bool $invert,
        DateTimeUnit $largest,
    ): DateInterval {
        $months = abs($months);
        $us = abs($us);
        $rank = $largest->rank();

        $interval = new DateInterval('PT0S');
        $interval->y = $rank === 0 ? intdiv($months, 12) : 0;
        $interval->m = $rank === 0 ? $months % 12 : $months;

        if ($rank <= DateTimeUnit::Day->rank()) {
            $interval->d = intdiv($us, 86_400_000_000);
            $us %= 86_400_000_000;
        }

        if ($rank <= DateTimeUnit::Hour->rank()) {
            $interval->h = intdiv($us, 3_600_000_000);
            $us %= 3_600_000_000;
        }

        if ($rank <= DateTimeUnit::Minute->rank()) {
            $interval->i = intdiv($us, 60_000_000);
            $us %= 60_000_000;
        }

        $interval->s = intdiv($us, 1_000_000);
        $interval->f = self::microsToFraction($us % 1_000_000);
        $interval->invert = $invert && self::isNonZero($interval) ? 1 : 0;

        return $interval;
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
    /**
     * PHP truncates `f * 1e6` when adding an interval, so nudge values like 0.515392 (515391.99999...)
     * up to the nearest double that truncates back to the exact microsecond count.
     */
    private static function microsToFraction(int $us): float
    {
        $f = $us / 1_000_000;

        while (abs((int) ($f * 1_000_000)) < abs($us)) {
            $f += $f * 2.0e-16;
        }

        return $f;
    }

    private static function toInterval(array $fields): DateInterval
    {
        $interval = new DateInterval('PT0S');
        $interval->y = $fields['y'];
        $interval->m = $fields['m'];
        $interval->d = $fields['d'];
        $interval->h = $fields['h'];
        $interval->i = $fields['i'];
        $interval->s = $fields['s'];
        $interval->f = self::microsToFraction($fields['us']);

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
