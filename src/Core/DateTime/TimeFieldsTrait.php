<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

/**
 * Time field accessors aligned with Chronos: properties are zero-padded strings, getters are integers.
 * The using class must provide `format(string $format): string`.
 */
trait TimeFieldsTrait
{
    public string $hour {
        get => $this->format('H');
    }

    public string $minute {
        get => $this->format('i');
    }

    public string $second {
        get => $this->format('s');
    }

    public string $microsecond {
        get => $this->format('u');
    }

    public function getHour(): int
    {
        return (int) $this->format('G');
    }

    public function getMinute(): int
    {
        return (int) $this->format('i');
    }

    public function getSecond(): int
    {
        return (int) $this->format('s');
    }

    public function getMicrosecond(): int
    {
        return (int) $this->format('u');
    }
}
