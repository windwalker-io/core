<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Windwalker\Core\DateTime\Contract\ChronosInterface;
use Windwalker\Core\DateTime\Contract\HasTimeInterface;

/**
 * A wall-clock time without date and time zone, precision up to microsecond.
 */
final class PlainTime implements ChronosInterface, HasTimeInterface
{
    use ChronosValueTrait;
    use TimeFieldsTrait;

    /**
     * Always on 1970-01-01 in UTC, so that no DST rule can ever affect the calculation.
     */
    private function __construct(private readonly DateTimeImmutable $dt)
    {
    }

    /**
     * @throws \RangeException When $overflow is Reject and a field is out of range.
     */
    public static function of(
        int $hour,
        int $minute,
        int $second = 0,
        int $microsecond = 0,
        DateOverflow $overflow = DateOverflow::Reject
    ): self {
        [$hour, $minute, $second, $microsecond] = ChronosMath::resolveTime(
            $hour,
            $minute,
            $second,
            $microsecond,
            $overflow
        );

        return new self(self::epoch()->setTime($hour, $minute, $second, $microsecond));
    }

    /**
     * Take the wall-clock time of the given object as is, without any time zone conversion.
     */
    public static function from(DateTimeInterface $date): self
    {
        return self::of(
            (int) $date->format('G'),
            (int) $date->format('i'),
            (int) $date->format('s'),
            (int) $date->format('u'),
        );
    }

    /**
     * @param  string  $time  Strict `H:i`, `H:i:s` or `H:i:s.u`.
     */
    public static function parse(string $time): self
    {
        foreach (['!H:i:s.u', '!H:i:s', '!H:i'] as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $time, new DateTimeZone('UTC'));

            if ($dt !== false && DateTimeImmutable::getLastErrors() === false) {
                return new self($dt);
            }
        }

        throw new InvalidArgumentException(sprintf('Invalid time "%s", expect H:i[:s[.u]].', $time));
    }

    public static function now(DateTimeZone|string|null $tz = null): self
    {
        return self::from(Clock::now($tz));
    }

    public function with(
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
        ?int $microsecond = null,
        DateOverflow $overflow = DateOverflow::Constrain,
    ): self {
        return self::of(
            $hour ?? $this->getHour(),
            $minute ?? $this->getMinute(),
            $second ?? $this->getSecond(),
            $microsecond ?? $this->getMicrosecond(),
            $overflow
        );
    }

    public function toPlainTime(): self
    {
        return $this;
    }

    public function compareTo(ChronosInterface $other): int
    {
        if (!$other instanceof self) {
            throw new \TypeError(sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other)));
        }

        return $this->dt <=> $other->dt;
    }

    public function toIso(): string
    {
        return $this->dt->format($this->getMicrosecond() === 0 ? 'H:i:s' : 'H:i:s.u');
    }

    /**
     * Wraps around midnight, like a clock.
     */
    protected function applyFields(array $fields, DateOverflow $overflow): static
    {
        ChronosMath::assertOnly($fields, ['h', 'i', 's', 'us'], self::class);

        $dt = ChronosMath::addTo($this->dt, $fields, $overflow);

        return new self($dt->setDate(1970, 1, 1));
    }

    private function format(string $format): string
    {
        return $this->dt->format($format);
    }

    private static function epoch(): DateTimeImmutable
    {
        return new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC'));
    }
}
