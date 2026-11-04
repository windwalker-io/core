<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\ErrorMessageOutputEvent;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Core\Schedule\ScheduleRunner;
use Windwalker\Core\Schedule\ScheduleRunnerOptions;
use Windwalker\Core\Schedule\Event\AfterScheduleRunEvent;
use Windwalker\Core\Schedule\Event\BeforeScheduleRunEvent;
use Windwalker\Core\Schedule\Event\LoopEndEvent;
use Windwalker\Core\Schedule\Event\LoopStartEvent;
use Windwalker\Core\Schedule\Event\ScheduleFailureEvent;
use Windwalker\Core\Schedule\Event\StartEvent;
use Windwalker\Core\Schedule\Event\StopEvent;
use Windwalker\Event\EventEmitter;
use Windwalker\Tests\Core\Schedule\Stub\ScheduleRunnerStub;

#[CoversClass(ScheduleRunner::class)]
class SccheduleRunnerTest extends TestCase
{
    private function observeEvents(ScheduleRunner $runner, array &$events): void
    {
        foreach ([
            StartEvent::class,
            LoopStartEvent::class,
            BeforeScheduleRunEvent::class,
            AfterScheduleRunEvent::class,
            ScheduleFailureEvent::class,
            LoopEndEvent::class,
            StopEvent::class,
        ] as $class) {
            $runner->on($class, static function (object $event) use (&$events) {
                $events[] = $event;
            });
        }
    }

    #[Test]
    public function successEventsExposeOrderedLifecycleResultAndElapsedDuration(): void
    {
        $schedule = new Schedule();
        $runner = $this->createWorker($schedule);
        $task = $schedule->cron('result', '* * * * *', static function () use ($runner) {
            $runner->advance(2);

            return 'done';
        });
        $events = [];
        $this->observeEvents($runner, $events);
        $time = $runner->date;

        $runner->run(new ScheduleRunnerOptions(once: true));

        self::assertSame(
            [StartEvent::class, LoopStartEvent::class, BeforeScheduleRunEvent::class,
                AfterScheduleRunEvent::class, LoopEndEvent::class, StopEvent::class],
            array_map(static fn(object $event) => $event::class, $events)
        );
        self::assertSame($schedule, $events[0]->schedule);
        self::assertSame($runner, $events[3]->runner);
        self::assertSame($task, $events[3]->scheduleEvent);
        self::assertSame($time, $events[3]->time);
        self::assertSame('done', $events[3]->result);
        self::assertSame(2.0, $events[3]->duration);
        self::assertSame(1, $events[4]->run);
        self::assertNull($events[4]->exception);
        self::assertSame('Single run completed.', $events[5]->reason);
        self::assertSame(1, $events[5]->runs);
        self::assertNull($events[5]->exception);
    }

    #[Test]
    public function failureEventsPrecedeCatchAndDoNotEmitSuccess(): void
    {
        $schedule = new Schedule();
        $runner = $this->createWorker($schedule);
        $failure = new RuntimeException('failed');
        $order = [];
        $schedule->cron('failure', '* * * * *', static function () use ($runner, $failure) {
            $runner->advance(3);

            throw $failure;
        })->catch(static function () use (&$order) {
            $order[] = 'catch';
        });
        $events = [];
        $this->observeEvents($runner, $events);
        $runner->on(ScheduleFailureEvent::class, static function () use (&$order) {
            $order[] = 'failure';
        });

        $runner->run(new ScheduleRunnerOptions(once: true));

        self::assertSame(['failure', 'catch'], $order);
        self::assertInstanceOf(ScheduleFailureEvent::class, $events[3]);
        self::assertSame($failure, $events[3]->exception);
        self::assertSame(3.0, $events[3]->duration);
        self::assertCount(6, $events);
        self::assertNull($events[4]->exception);
        self::assertNull($events[5]->exception);
    }

    #[Test]
    public function emptyTicksEmitLoopEventsAndStopReportsTheLimit(): void
    {
        $runner = $this->createWorker(new Schedule());
        $events = [];
        $this->observeEvents($runner, $events);

        $runner->run(new ScheduleRunnerOptions(maxRuns: 2));

        self::assertSame(
            [StartEvent::class, LoopStartEvent::class, LoopEndEvent::class,
                LoopStartEvent::class, LoopEndEvent::class, StopEvent::class],
            array_map(static fn(object $event) => $event::class, $events)
        );
        self::assertSame(2, $events[4]->run);
        self::assertSame('Max runs reached.', $events[5]->reason);
        self::assertSame(2, $events[5]->runs);
    }

    #[Test]
    public function listenerFailuresPropagateWithFinalLifecycleEvents(): void
    {
        $schedule = new Schedule();
        $schedule->cron('task', '* * * * *', static fn() => 'ok');
        $runner = $this->createWorker($schedule);
        $events = [];
        $this->observeEvents($runner, $events);
        $failure = new RuntimeException('listener failed');
        $runner->on(AfterScheduleRunEvent::class, static fn() => throw $failure);

        try {
            $runner->run(new ScheduleRunnerOptions(once: true));
            self::fail('Listener failures must propagate.');
        } catch (RuntimeException $e) {
            self::assertSame($failure, $e);
        }

        self::assertCount(6, $events);
        self::assertSame($failure, $events[4]->exception);
        self::assertSame($failure, $events[5]->exception);
        self::assertSame('Runner failed.', $events[5]->reason);
    }

    #[Test]
    public function beforeListenersCanStopDispatchAndEventsReachExternalDealers(): void
    {
        $schedule = new Schedule();
        $calls = 0;
        $schedule->cron('task', '* * * * *', static function () use (&$calls) {
            $calls++;
        });
        $runner = $this->createWorker($schedule);
        $dealer = new EventEmitter();
        $stop = null;
        $dealer->on(BeforeScheduleRunEvent::class, static function (BeforeScheduleRunEvent $event) {
            $event->runner->stop('External shutdown.');
        });
        $dealer->on(StopEvent::class, static function (StopEvent $event) use (&$stop) {
            $stop = $event;
        });
        $runner->addEventDealer($dealer);

        $runner->run(new ScheduleRunnerOptions(once: true));

        self::assertSame(0, $calls);
        self::assertSame('External shutdown.', $stop->reason);
        self::assertNull($stop->exception);
    }

    private function createWorker(Schedule $schedule, ?ApplicationInterface $app = null): ScheduleRunnerStub
    {
        if ($app === null) {
            $app = $this->createStub(ApplicationInterface::class);
            $app->method('call')->willReturnCallback(static fn(callable $handler) => $handler());
        }

        $service = $this->createStub(ScheduleService::class);
        $service->method('getSchedule')->willReturn($schedule);
        $service->method('runEvent')->willReturnCallback(static fn($event) => $event->execute());

        return new ScheduleRunnerStub($app, $service);
    }

    #[Test]
    public function optionsCanBeReusedWithoutTimezoneMutationAndAreSnapshottedPerRun(): void
    {
        $schedule = new Schedule();
        $runner = $this->createWorker($schedule);
        $options = new ScheduleRunnerOptions(timezone: 'UTC', maxRuns: 2);
        $calls = 0;
        $schedule->cron('task', '* * * * *', static function () use ($options, &$calls) {
            $calls++;
            $options->once = true;
            $options->names = ['other'];
        });

        $runner->run($options);

        self::assertSame(2, $calls);
        self::assertSame(30, $runner->sleeps);
        self::assertSame('UTC', $options->timezone);

        $runner->run($options);

        self::assertSame(2, $calls);
        self::assertSame(30, $runner->sleeps);
    }

    #[Test]
    public function pollsWithoutRepeatingTheSameMinute(): void
    {
        $schedule = new Schedule();
        $calls = 0;
        $schedule->cron('every-minute', '* * * * *', static function () use (&$calls) {
            $calls++;
        });
        $worker = $this->createWorker($schedule);

        $worker->run(new ScheduleRunnerOptions(maxRuns: 3));

        self::assertSame(3, $calls);
        self::assertSame(90, $worker->sleeps);
        self::assertSame('00:02:00', $worker->date->format('H:i:s'));
    }

    #[Test]
    public function onceHonorsDueTimeNamesTagsAndTimezone(): void
    {
        $schedule = new Schedule();
        $called = [];

        foreach (['match', 'wrong-name', 'wrong-tag', 'not-due'] as $name) {
            $expression = $name === 'not-due' ? '0 9 * * *' : '0 8 * * *';
            $schedule->cron($name, $expression, static function () use (&$called, $name) {
                $called[] = $name;
            })->tags($name === 'wrong-tag' ? 'other' : 'selected');
        }

        $worker = $this->createWorker($schedule);
        $worker->date = new DateTimeImmutable('2026-01-01 00:00:00 UTC');
        $worker->run(new ScheduleRunnerOptions(
            names: ['match', 'wrong-tag', 'not-due'],
            tags: ['selected'],
            timezone: 'Asia/Taipei',
            once: true
        ));

        self::assertSame(['match'], $called);
        self::assertSame(0, $worker->sleeps);
    }

    #[Test]
    public function lifetimeStopsAnEmptyWorkerUsingElapsedTime(): void
    {
        $worker = $this->createWorker(new Schedule());

        $worker->run(new ScheduleRunnerOptions(lifetime: 3));

        self::assertSame(3, $worker->sleeps);
        self::assertSame(3.0, $worker->elapsed);
    }

    #[Test]
    public function maxRunsCountsEmptyMinutes(): void
    {
        $worker = $this->createWorker(new Schedule());

        $worker->run(new ScheduleRunnerOptions(maxRuns: 2));

        self::assertSame(30, $worker->sleeps);
    }

    #[Test]
    public function aSlowEventCanFinishAfterTheLifetimeWithoutStartingAnotherTick(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $calls = 0;
        $schedule->cron('slow', '* * * * *', static function () use ($worker, &$calls) {
            $calls++;
            $worker->advance(125);
        });

        $worker->run(new ScheduleRunnerOptions(lifetime: 2));

        self::assertSame(1, $calls);
        self::assertSame(126.0, $worker->elapsed);
    }

    #[Test]
    public function backwardClockChangesDoNotReplayProcessedMinutes(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $calls = 0;
        $schedule->cron('rewind', '* * * * *', static function () use ($worker, &$calls) {
            $calls++;

            if ($calls === 1) {
                $worker->advance(-60);
            }
        });

        $worker->run(new ScheduleRunnerOptions(maxRuns: 2));

        self::assertSame(2, $calls);
        self::assertSame(90, $worker->sleeps);
        self::assertSame('00:01:00', $worker->date->format('H:i:s'));
    }

    #[Test]
    public function slowEventsSkipMissedMinutesRatherThanReplayingThem(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $times = [];
        $schedule->cron('slow', '* * * * *', static function () use ($worker, &$times) {
            $times[] = $worker->date->format('H:i:s');
            $worker->advance(125);
        });

        $worker->run(new ScheduleRunnerOptions(maxRuns: 2));

        self::assertSame(['00:00:30', '00:02:36'], $times);
        self::assertSame(1, $worker->sleeps);
    }

    #[Test]
    public function stopFinishesTheCurrentEventAndSkipsRemainingEvents(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $called = [];
        $schedule->cron('stop', '* * * * *', static function () use ($worker, &$called) {
            $called[] = 'before-stop';
            $worker->stop();
            $called[] = 'after-stop';
        });
        $schedule->cron('skipped', '* * * * *', static function () use (&$called) {
            $called[] = 'skipped';
        });

        $worker->run();

        self::assertSame(['before-stop', 'after-stop'], $called);
        self::assertSame(0, $worker->sleeps);
    }

    #[Test]
    public function eventFailuresAreReportedAndDoNotStopFollowingEvents(): void
    {
        $schedule = new Schedule();
        $failure = new RuntimeException('event failed');
        $caught = null;
        $continued = false;
        $errors = [];
        $schedule->cron('failed', '* * * * *', static fn() => throw $failure)
            ->catch(static function (Throwable $exception) use (&$caught) {
                $caught = $exception;
            });
        $schedule->cron('uncaught', '* * * * *', static fn() => throw new RuntimeException('uncaught'));
        $schedule->cron('following', '* * * * *', static function () use (&$continued) {
            $continued = true;
        });

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('call')
            ->with(
                $schedule->getEvents()->current()->getCatch(),
                ['exception' => $failure, 'e' => $failure, Throwable::class => $failure]
            )
            ->willReturnCallback(static fn(callable $handler, array $args) => $handler($args['exception']));
        $worker = $this->createWorker($schedule, $app);
        $worker->on(ErrorMessageOutputEvent::class, static function (ErrorMessageOutputEvent $event) use (&$errors) {
            $errors[] = $event->messages;
        });

        $worker->run(new ScheduleRunnerOptions(once: true));

        self::assertSame($failure, $caught);
        self::assertTrue($continued);
        self::assertSame(['event failed', 'uncaught'], $errors);
    }

    #[Test]
    public function callbackFailuresPropagateAndReleaseTheWorkerForAnotherRun(): void
    {
        $schedule = new Schedule();
        $schedule->cron('failed', '* * * * *', static fn() => throw new RuntimeException('event failed'))
            ->catch(static fn() => throw new RuntimeException('callback failed'));
        $worker = $this->createWorker($schedule);

        try {
            $worker->run(new ScheduleRunnerOptions(once: true));
            self::fail('The callback exception must propagate.');
        } catch (RuntimeException $e) {
            self::assertSame('callback failed', $e->getMessage());
        }

        $schedule->setEvents([]);
        $worker->run(new ScheduleRunnerOptions(once: true));

        self::assertSame(0, $worker->sleeps);
    }

    #[Test]
    public function aStoppedWorkerCanRunAgain(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $calls = 0;
        $schedule->cron('stop', '* * * * *', static function () use ($worker, &$calls) {
            $calls++;
            $worker->stop();
        });

        $worker->run();
        $worker->run();

        self::assertSame(2, $calls);
    }

    #[Test]
    public function reentrantRunsAreRejected(): void
    {
        $schedule = new Schedule();
        $worker = $this->createWorker($schedule);
        $exception = null;
        $schedule->cron('nested', '* * * * *', static function () use ($worker, &$exception) {
            try {
                $worker->run(new ScheduleRunnerOptions(once: true));
            } catch (LogicException $e) {
                $exception = $e;
            }
        });

        $worker->run(new ScheduleRunnerOptions(once: true));

        self::assertInstanceOf(LogicException::class, $exception);
    }

    #[Test]
    public function negativeLifetimeIsRejected(): void
    {
        $worker = $this->createWorker(new Schedule());
        $this->expectException(InvalidArgumentException::class);

        $worker->run(new ScheduleRunnerOptions(lifetime: -1));
    }

    #[Test]
    public function negativeMaxRunsIsRejected(): void
    {
        $worker = $this->createWorker(new Schedule());
        $this->expectException(InvalidArgumentException::class);

        $worker->run(new ScheduleRunnerOptions(maxRuns: -1));
    }

    #[Test]
    public function invalidTimezoneIsRejectedBeforeDispatch(): void
    {
        $worker = $this->createWorker(new Schedule());
        $this->expectException(\DateInvalidTimeZoneException::class);

        $worker->run(new ScheduleRunnerOptions(timezone: 'invalid/timezone', once: true));
    }
}
