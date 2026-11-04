<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Throwable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\DateTime\Clock;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Schedule\Event\AfterScheduleRunEvent;
use Windwalker\Core\Schedule\Event\BeforeScheduleRunEvent;
use Windwalker\Core\Schedule\Event\LoopEndEvent;
use Windwalker\Core\Schedule\Event\LoopStartEvent;
use Windwalker\Core\Schedule\Event\ScheduleFailureEvent;
use Windwalker\Core\Schedule\Event\StartEvent;
use Windwalker\Core\Schedule\Event\StopEvent;
use Windwalker\DI\Attributes\Service;

#[Service]
class ScheduleRunner
{
    use MessageOutputTrait;

    protected bool $running = false;

    protected bool $shouldStop = false;

    protected string $stopReason = 'Stop requested.';

    public function __construct(
        protected ApplicationInterface $app,
        protected ScheduleService $scheduleService
    ) {}

    public function run(ScheduleRunnerOptions $options = new ScheduleRunnerOptions()): void
    {
        $lifetime = $options->lifetime;
        $names = $options->names;
        $tags = $options->tags;
        $timezone = $options->timezone;
        $once = $options->once;
        $maxRuns = $options->maxRuns;

        if ($lifetime < 0 || $maxRuns < 0) {
            throw new InvalidArgumentException('Lifetime and max runs must be non-negative.');
        }

        if ($this->running) {
            throw new LogicException('The schedule worker is already running.');
        }

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        $schedule = $this->scheduleService->getSchedule();
        $startedAt = $this->monotonicTime();
        $lastMinute = null;
        $runs = 0;
        $this->shouldStop = false;
        $this->stopReason = 'Stop requested.';
        $this->running = true;
        $exception = null;

        try {
            $this->emit(new StartEvent($this, $schedule));
            $this->emitMessage('Schedule worker started.');

            while (!$this->shouldStop) {
                if ($lifetime > 0 && $this->monotonicTime() - $startedAt >= $lifetime) {
                    $this->stopReason = 'Lifetime reached.';
                    break;
                }

                $now = $this->now();
                $minute = (int) floor($now->getTimestamp() / 60);

                if ($lastMinute === null || $minute > $lastMinute) {
                    // Mark before dispatch: slow tasks must not replay the same minute.
                    $lastMinute = $minute;
                    $runs++;
                    $loopException = null;

                    try {
                        $this->emit(new LoopStartEvent($this, $now, $runs));
                        $this->runDueEvents($schedule, $now, $names, $tags, $timezone);
                    } catch (Throwable $e) {
                        $loopException = $e;

                        throw $e;
                    } finally {
                        $this->emit(new LoopEndEvent($this, $now, $runs, $loopException));
                    }

                    if ($once || ($maxRuns > 0 && $runs >= $maxRuns)) {
                        if (!$this->shouldStop) {
                            $this->stopReason = $once ? 'Single run completed.' : 'Max runs reached.';
                        }
                        break;
                    }
                }

                if (!$this->shouldStop) {
                    $this->sleep();
                }
            }
        } catch (Throwable $e) {
            $exception = $e;
            $this->stopReason = 'Runner failed.';

            throw $e;
        } finally {
            $this->running = false;
            $this->emit(new StopEvent($this, $this->stopReason, $runs, $exception));
            $this->emitMessage('Schedule worker stopped.');
        }
    }

    /**
     * Request a graceful stop after the current event finishes.
     */
    public function stop(string $reason = 'Stop requested.'): void
    {
        $this->shouldStop = true;
        $this->stopReason = $reason;
    }

    protected function runDueEvents(
        Schedule $schedule,
        DateTimeImmutable $now,
        array $names,
        array $tags,
        ?DateTimeZone $timezone
    ): void {
        Logger::info('system/schedule', 'Run schedule');

        foreach ($schedule->getDueEvents($tags, $now, $timezone) as $event) {
            if ($this->shouldStop) {
                break;
            }

            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            Logger::info('system/schedule', '  [Event] ' . $event->getName());
            $this->emitMessage(sprintf('[%s] Run schedule: %s', $now->format('c'), $event->getName()));
            $this->emit(new BeforeScheduleRunEvent($this, $event, $now));

            if ($this->shouldStop) {
                break;
            }

            $startedAt = $this->monotonicTime();

            try {
                $result = $this->scheduleService->runEvent($event);
            } catch (Throwable $e) {
                Logger::error(
                    'system/schedule',
                    sprintf('  [Error %s] %s - %s:%s', $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine())
                );
                Logger::error('system/schedule-error', $e);
                $this->emitErrorMessage($e->getMessage());
                $this->emit(
                    new ScheduleFailureEvent($this, $event, $now, $e, $this->monotonicTime() - $startedAt)
                );

                if ($catch = $event->getCatch()) {
                    $this->app->call($catch, ['exception' => $e, 'e' => $e, Throwable::class => $e]);
                }

                continue;
            }

            $this->emit(new AfterScheduleRunEvent($this, $event, $now, $result, $this->monotonicTime() - $startedAt));
        }
    }

    protected function now(): DateTimeImmutable
    {
        return Clock::now();
    }

    protected function monotonicTime(): float
    {
        return hrtime(true) / 1_000_000_000;
    }

    protected function sleep(): void
    {
        sleep(1);
    }
}
