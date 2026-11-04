<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Queue\ScheduleWorker;
use Windwalker\Core\Queue\ScheduleWorkerOptions;
use Windwalker\Queue\Event\AfterJobRunEvent;
use Windwalker\Queue\Event\BeforeJobRunEvent;
use Windwalker\Queue\Event\JobFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;
use Windwalker\Queue\RunnerOptions;
use Windwalker\Queue\Worker;
use Windwalker\Tests\Core\Queue\Stub\StubInvokableJob;
use Windwalker\Tests\Core\Queue\Stub\StubQueueDriver;
use Windwalker\Tests\Core\Queue\Stub\StubQueueFailer;

class ScheduleWorkerTest extends TestCase
{
    #[Test]
    public function createInvokerForwardsToAppCall(): void
    {
        $job = static fn() => null;
        $message = new QueueMessage($job);
        $controller = $message->makeJobController();

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('call')
            ->with(
                $job,
                [
                    'jobController' => $controller,
                    'controller' => $controller,
                    JobController::class => $controller,
                ]
            )
            ->willReturn('invoked-result');

        $worker = new ScheduleWorker($app);

        self::assertSame('invoked-result', ($worker->createInvoker())($controller, $job));
    }

    #[Test]
    public function runByCommandBuildsCliCommandWithAllOptionsAndForwardsOutputToEmitMessage(): void
    {
        $capturedCommand = null;
        $capturedOutput = null;

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('mustRunProcess')
            ->with(
                $this->callback(function (string $cmd) use (&$capturedCommand) {
                    $capturedCommand = $cmd;

                    return true;
                }),
                null,
                $this->callback(function ($output) use (&$capturedOutput) {
                    $capturedOutput = $output;

                    return true;
                })
            )
            ->willReturn($this->createStub(Process::class));

        $options = new ScheduleWorkerOptions(
            once: true,
            backoff: 5,
            force: true,
            sleep: 2,
            tries: 3,
            timeout: 60,
            maxRuns: 10,
            shuffleChannels: true,
            stopWhenEmpty: true,
            enqueuer: true,
        );

        $worker = new ScheduleWorker($app);
        $worker->runByCommand(120, 'redis', ['default', 'high'], $options);

        self::assertStringStartsWith('php windwalker queue:worker ', $capturedCommand);
        self::assertStringContainsString(
            escapeshellarg('default') . ' ' . escapeshellarg('high'),
            $capturedCommand
        );
        self::assertStringContainsString('--lifetime ' . escapeshellarg('120'), $capturedCommand);
        self::assertStringContainsString('--connection ' . escapeshellarg('redis'), $capturedCommand);
        self::assertStringContainsString('--once ', $capturedCommand);
        self::assertStringContainsString('--backoff ' . escapeshellarg('5'), $capturedCommand);
        self::assertStringContainsString('--timeout ' . escapeshellarg('60'), $capturedCommand);
        self::assertStringContainsString('--tries ' . escapeshellarg('3'), $capturedCommand);
        self::assertStringContainsString('--sleep ' . escapeshellarg('2'), $capturedCommand);
        self::assertStringContainsString('--force ', $capturedCommand);
        self::assertStringContainsString('--max-runs ' . escapeshellarg('10'), $capturedCommand);
        self::assertStringContainsString('--shuffle-channels ', $capturedCommand);
        self::assertStringContainsString('--stop-when-empty ', $capturedCommand);
        self::assertStringContainsString('--enqueuer ', $capturedCommand);

        self::assertIsCallable($capturedOutput);

        $emitted = null;
        $worker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $capturedOutput('out', 'hello');

        self::assertSame('hello', $emitted->messages);
        self::assertFalse($emitted->newLine);
    }

    #[Test]
    public function runByCommandOmitsOptionalCliFlagsWhenNotProvided(): void
    {
        $capturedCommand = null;

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('mustRunProcess')
            ->with(
                $this->callback(function (string $cmd) use (&$capturedCommand) {
                    $capturedCommand = $cmd;

                    return true;
                })
            )
            ->willReturn($this->createStub(Process::class));

        $worker = new ScheduleWorker($app);
        $worker->runByCommand(0);

        // Defaults still contribute --timeout/--tries/--sleep (non-zero RunnerOptions defaults);
        // only the zero/false-valued options (backoff, force, maxRuns, ...) are omitted.
        self::assertSame(
            'php windwalker queue:worker --timeout ' . escapeshellarg('60')
            . ' --tries ' . escapeshellarg('5')
            . ' --sleep ' . escapeshellarg('1') . ' ',
            $capturedCommand
        );
        self::assertStringNotContainsString('--lifetime', $capturedCommand);
        self::assertStringNotContainsString('--connection', $capturedCommand);
        self::assertStringNotContainsString('--once', $capturedCommand);
        self::assertStringNotContainsString('--backoff', $capturedCommand);
        self::assertStringNotContainsString('--force', $capturedCommand);
        self::assertStringNotContainsString('--max-runs', $capturedCommand);
        self::assertStringNotContainsString('--shuffle-channels', $capturedCommand);
        self::assertStringNotContainsString('--stop-when-empty', $capturedCommand);
        self::assertStringNotContainsString('--enqueuer', $capturedCommand);
    }

    protected function invokeListenToWorker(ScheduleWorker $scheduleWorker, Worker $worker, string $connection, bool $once = false): void
    {
        $ref = new \ReflectionMethod($scheduleWorker, 'listenToWorker');
        $ref->invoke($scheduleWorker, $worker, $connection, $once);
    }

    /**
     * Simulates `ApplicationInterface::call()`'s real contract of resolving callable
     * parameters by name or type from the given args map, since a mock cannot reuse
     * the real DI-backed implementation.
     */
    protected function createCallInvoker(): \Closure
    {
        return function (callable $callable, array $args = []) {
            $ref = new \ReflectionFunction(\Closure::fromCallable($callable));
            $params = [];

            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();
                $type = $param->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if (array_key_exists($name, $args)) {
                    $params[] = $args[$name];
                } elseif ($typeName !== null && array_key_exists($typeName, $args)) {
                    $params[] = $args[$typeName];
                }
            }

            return $callable(...$params);
        };
    }

    protected function createWorker(): Worker
    {
        return new Worker(new Queue(new StubQueueDriver()), new RunnerOptions());
    }

    protected function makeJobController(Worker $worker, ?callable $job = null): JobController
    {
        $message = new QueueMessage($job ?? static fn() => null);
        $message->setId('msg-1');
        $message->setChannel('default');

        return $message->makeJobController($worker->getInvoker());
    }

    #[Test]
    public function listenToWorkerEmitsMessageOnBeforeJobRun(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $worker->emit(new BeforeJobRunEvent($controller, $worker, $worker->getQueue()));

        self::assertStringContainsString('msg-1', $emitted->messages);
    }

    #[Test]
    public function listenToWorkerEmitsMessageOnAfterJobRunAndRunsJobEndScripts(): void
    {
        $scriptCalled = false;

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('config')
            ->with('queue.job_end_scripts')
            ->willReturn(
                function () use (&$scriptCalled) {
                    $scriptCalled = true;
                }
            );
        $app->method('call')->willReturnCallback($this->createCallInvoker());

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $worker->emit(new AfterJobRunEvent($controller, $worker, $worker->getQueue()));

        self::assertStringContainsString('msg-1', $emitted->messages);
        self::assertStringContainsString('END', $emitted->messages);
        self::assertTrue($scriptCalled);
    }

    #[Test]
    public function listenToWorkerEmitsDeferReasonOnAfterJobRunWhenDeferred(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('config')->willReturn([]);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);
        $controller->defer(5, 'try again later');

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $worker->emit(new AfterJobRunEvent($controller, $worker, $worker->getQueue()));

        self::assertStringContainsString('try again later', $emitted->messages);
    }

    #[Test]
    public function listenToWorkerEmitsAbandonedReasonOnAfterJobRunWhenAbandoned(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('config')->willReturn([]);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);
        $controller->abandoned('no longer needed');

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $worker->emit(new AfterJobRunEvent($controller, $worker, $worker->getQueue()));

        self::assertStringContainsString('END', $emitted->messages);
    }

    #[Test]
    public function listenToWorkerHandlesJobFailureWhenMessageWillRetry(): void
    {
        $addedMessages = [];

        $app = $this->createMock(ApplicationInterface::class);
        $app->method('addMessage')
            ->willReturnCallback(function ($message, $type = 'info') use (&$addedMessages, $app) {
                $addedMessages[] = [$message, $type];

                return $app;
            });
        $app->expects($this->never())->method('service');

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default', false);

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $exception = new \RuntimeException('boom');

        $worker->emit(
            new JobFailureEvent(
                exception: $exception,
                controller: $controller,
                runner: $worker,
                queue: $worker->getQueue(),
                backoff: 10,
            )
        );

        self::assertCount(1, $addedMessages);
        self::assertStringContainsString('Will retry after 10 seconds', $addedMessages[0][0]);
        self::assertSame('error', $addedMessages[0][1]);
        self::assertStringContainsString('boom', $emitted->messages);
    }

    #[Test]
    public function listenToWorkerDeletesFailerMessageWhenDeletedAndNotAbandoned(): void
    {
        $failer = new StubQueueFailer();

        $app = $this->createMock(ApplicationInterface::class);
        $app->method('addMessage')->willReturnSelf();
        $app->expects($this->once())
            ->method('service')
            ->with(QueueFailerInterface::class)
            ->willReturn($failer);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker, new StubInvokableJob());
        $controller->maxAttemptsExceeds = true;
        $controller->message->setDeleted(true);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default', false);
        $scheduleWorker->onMessages(fn() => null);

        $worker->emit(
            new JobFailureEvent(
                exception: new \RuntimeException('boom'),
                controller: $controller,
                runner: $worker,
                queue: $worker->getQueue(),
                maxAttemptsExceeds: true,
            )
        );

        self::assertCount(1, $failer->added);
        self::assertSame('default', $failer->added[0][0]);
    }

    #[Test]
    public function listenToWorkerRethrowsExceptionWhenOnceOptionEnabled(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('addMessage')->willReturnSelf();

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $controller = $this->makeJobController($worker);
        $controller->abandoned('done');
        $controller->message->setDeleted(false);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default', true);

        $exception = new \RuntimeException('fatal failure');

        $this->expectExceptionObject($exception);

        $worker->emit(
            new JobFailureEvent(
                exception: $exception,
                controller: $controller,
                runner: $worker,
                queue: $worker->getQueue(),
            )
        );
    }

    #[Test]
    public function listenToWorkerPausesActiveWorkerDuringMaintenance(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isMaintenance')->willReturn(true);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $worker->setState(Worker::STATE_ACTIVE);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $worker->emit(new LoopStartEvent($worker, $worker->getQueue()));

        self::assertSame(Worker::STATE_PAUSE, $worker->getState());
    }

    #[Test]
    public function listenToWorkerResumesPausedWorkerWhenMaintenanceOver(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isMaintenance')->willReturn(false);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $worker->setState(Worker::STATE_PAUSE);

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $worker->emit(new LoopStartEvent($worker, $worker->getQueue()));

        self::assertSame(Worker::STATE_ACTIVE, $worker->getState());
    }

    #[Test]
    public function listenToWorkerEmitsMessagesOnLoopFailure(): void
    {
        $app = $this->createStub(ApplicationInterface::class);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = [];
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted[] = $event;
        });

        $worker->emit(new LoopFailureEvent($worker, 'loop failed', new \RuntimeException('bad loop')));

        self::assertCount(2, $emitted);
        self::assertStringContainsString('bad loop', $emitted[0]->messages);
        self::assertStringContainsString('bad loop', $emitted[1]->messages);
    }

    #[Test]
    public function listenToWorkerRunsLoopEndScripts(): void
    {
        $scriptCalled = false;

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('config')
            ->with('queue.loop_end_scripts')
            ->willReturn(
                function () use (&$scriptCalled) {
                    $scriptCalled = true;
                }
            );
        $app->method('call')->willReturnCallback($this->createCallInvoker());

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $worker->emit(new LoopEndEvent($worker, $worker->getQueue()));

        self::assertTrue($scriptCalled);
    }

    #[Test]
    public function listenToWorkerEmitsMessageOnStop(): void
    {
        $app = $this->createStub(ApplicationInterface::class);

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();

        $this->invokeListenToWorker($scheduleWorker, $worker, 'default');

        $emitted = null;
        $scheduleWorker->onMessages(function (MessageOutputEvent $event) use (&$emitted) {
            $emitted = $event;
        });

        $worker->emit(new StopEvent('stopped manually', $worker, $worker->getQueue()));

        self::assertSame('stopped manually', $emitted->messages);
    }

    protected function invokeRunConfigScripts(
        ScheduleWorker $scheduleWorker,
        string $configName,
        Worker $worker,
        ?object $event,
        string $connection
    ): void {
        $ref = new \ReflectionMethod($scheduleWorker, 'runConfigScripts');
        $ref->invoke($scheduleWorker, $configName, $worker, $event, $connection);
    }

    #[Test]
    public function runConfigScriptsDoesNothingWhenNoScriptsConfigured(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('config')
            ->with('queue.init_scripts')
            ->willReturn(null);
        $app->expects($this->never())->method('call');

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();

        $this->invokeRunConfigScripts($scheduleWorker, 'init_scripts', $worker, null, 'default');

        self::assertTrue(true);
    }

    #[Test]
    public function runConfigScriptsInvokesSingleCallableScript(): void
    {
        $calledWith = null;

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('config')
            ->with('queue.job_end_scripts')
            ->willReturn(
                function (Worker $worker, string $connection) use (&$calledWith) {
                    $calledWith = [$worker, $connection];
                }
            );
        $app->method('call')->willReturnCallback($this->createCallInvoker());

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $event = new LoopEndEvent($worker, $worker->getQueue());

        $this->invokeRunConfigScripts($scheduleWorker, 'job_end_scripts', $worker, $event, 'default');

        self::assertSame([$worker, 'default'], $calledWith);
    }

    #[Test]
    public function runConfigScriptsInvokesEachScriptInArray(): void
    {
        $calls = [];

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('config')
            ->with('queue.job_end_scripts')
            ->willReturn(
                [
                    function () use (&$calls) {
                        $calls[] = 'first';
                    },
                    function () use (&$calls) {
                        $calls[] = 'second';
                    },
                ]
            );
        $app->method('call')->willReturnCallback($this->createCallInvoker());

        $scheduleWorker = new ScheduleWorker($app);
        $worker = $this->createWorker();
        $event = new LoopEndEvent($worker, $worker->getQueue());

        $this->invokeRunConfigScripts($scheduleWorker, 'job_end_scripts', $worker, $event, 'default');

        self::assertSame(['first', 'second'], $calls);
    }
}
