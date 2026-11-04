<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule\Stub;

use DateTimeImmutable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Core\Schedule\ScheduleRunner;

/**
 * Virtual wall and monotonic clocks for SccheduleRunner lifecycle tests.
 * Remove when those tests no longer need deterministic polling and slow tasks.
 */
class ScheduleRunnerStub extends ScheduleRunner
{
    public DateTimeImmutable $date;

    public float $elapsed = 0;

    public int $sleeps = 0;

    public function __construct(ApplicationInterface $app, ScheduleService $service)
    {
        parent::__construct($app, $service);

        $this->date = new DateTimeImmutable('2026-01-01 00:00:30 UTC');
    }

    public function advance(int $seconds): void
    {
        $this->date = $this->date->modify(sprintf('%+d seconds', $seconds));
        $this->elapsed += abs($seconds);
    }

    protected function now(): DateTimeImmutable
    {
        return $this->date;
    }

    protected function monotonicTime(): float
    {
        return $this->elapsed;
    }

    protected function sleep(): void
    {
        $this->sleeps++;
        $this->advance(1);
    }
}
