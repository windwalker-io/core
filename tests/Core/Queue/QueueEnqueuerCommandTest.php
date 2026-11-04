<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IO;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Queue\Command\QueueEnqueuerCommand;
use Windwalker\Core\Queue\Command\QueueWorkerCommand;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Testing\Stub\SpyLogger;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Queue\Driver\NullQueueDriver;
use Windwalker\Queue\Event\AfterEnqueueEvent;
use Windwalker\Queue\Event\AfterJobRunEvent;
use Windwalker\Queue\Event\BeforeEnqueueEvent;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Queue;
use Windwalker\Tests\Core\Queue\Stub\EnqueuerCommandStub;
use Windwalker\Tests\Core\Queue\Stub\ScheduleEnqueuerStub;
use Windwalker\Core\Queue\ScheduleEnqueuer;
use Windwalker\Core\Queue\ScheduleEnqueuerOptions;
use Windwalker\Core\Queue\ScheduleWorker;
use Windwalker\Queue\Worker;
use Windwalker\Queue\Enqueuer;

class QueueEnqueuerCommandTest extends TestCase
{
    #[Test]
    public function workerEntrypointsShareScriptsAndPreserveCommandOnlyIoArguments(): void
    {
        $worker = new Worker(new Queue(new NullQueueDriver()));
        $io = $this->createIO(new QueueWorkerCommand($this->app), ['--once' => true]);
        $calls = [];
        $this->config->setDeep('queue.init_scripts', [
            static function ($worker, $event, $connection) use (&$calls) {
                $calls[] = [$worker, $event, $connection];
            },
            static function (Worker $runner) use (&$calls) {
                $calls[] = $runner;
            },
        ]);
        $command = new QueueWorkerCommand($this->app);
        new \ReflectionMethod($command, 'runConfigScripts')->invoke(
            $command,
            'init_scripts',
            $worker,
            null,
            $io,
            'primary'
        );
        $adapter = new ScheduleWorker($this->app);
        new \ReflectionMethod($adapter, 'runConfigScripts')->invoke(
            $adapter,
            'init_scripts',
            $worker,
            null,
            'primary'
        );
        self::assertSame([[$worker, null, 'primary'], $worker, [$worker, null, 'primary'], $worker], $calls);

        $this->config->setDeep('queue.job_end_scripts', static function (
            Worker $runner,
            AfterJobRunEvent $event,
            IOInterface $io
        ) use ($worker) {
            self::assertSame($worker, $runner);
            self::assertSame($worker, $event->runner);
            self::assertTrue($io->getOption('once'));
        });
        $controller = new \Windwalker\Queue\QueueMessage(new \Windwalker\Tests\Core\Queue\Stub\StubInvokableJob())->makeJobController();
        $event = new AfterJobRunEvent($controller, $worker, $worker->getQueue());
        new \ReflectionMethod($command, 'runConfigScripts')->invoke(
            $command,
            'job_end_scripts',
            $worker,
            $event,
            $io,
            'primary'
        );
    }

    #[Test]
    public function schedulerRunSharesHandlerAndScriptWiringWithoutChangingApplicationIo(): void
    {
        $this->handlers('$enqueuer->channel("default", static fn($controller) => $controller->channel);');
        $order = [];
        $this->config->setDeep('queue.enqueuer.init_scripts', static function ($event, $connection, IOInterface $io) use (&$order) {
            $order[] = ['init', $event, $connection, $io->getOption('once')];
            $io->writeln('init output');
        });
        $this->config->setDeep('queue.enqueuer.loop_end_scripts', static function ($enqueuer) use (&$order) {
            $order[] = ['end', $enqueuer];
        });
        $this->app->on(AfterEnqueueEvent::class, static function ($event) use (&$order) {
            $order[] = ['handler', $event->result];
        });
        $output = new BufferedOutput();
        $outerIO = new IO(new ArrayInput([]), new NullOutput(), new Command());
        $this->app->container->share(IOInterface::class, $outerIO);
        $runner = new ScheduleEnqueuerStub($this->app);
        $runner->setMessageOutput($output);
        $options = new ScheduleEnqueuerOptions(sleep: 0, timeout: 0, maxRuns: 2);
        $runner->run(297, options: $options);

        self::assertSame(['init', 'handler', 'end', 'handler', 'end'], array_column($order, 0));
        self::assertSame(['init', null, 'primary', false], $order[0]);
        self::assertSame('default', $order[1][1]);
        self::assertSame(297, $runner->enqueuer->options->lifetime);
        self::assertSame(2, $options->maxRuns);
        self::assertSame($outerIO, $this->app->retrieve(IOInterface::class));
        self::assertStringContainsString('init output', $output->fetch());
    }

    #[Test]
    public function schedulerOnceRunsEachChannelWithoutEnteringLoop(): void
    {
        $this->handlers('$enqueuer->channel("alpha", static fn() => "a"); $enqueuer->channel("beta", static fn() => "b");');
        $results = [];
        $this->app->on(AfterEnqueueEvent::class, static function ($event) use (&$results) {
            $results[] = $event->result;
        });
        $runner = new ScheduleEnqueuerStub($this->app);
        $runner->run(0, channels: '*', options: new ScheduleEnqueuerOptions(once: true));
        self::assertSame(['a', 'b'], $results);
        self::assertNull($runner->enqueuer->pid);
    }

    #[Test]
    public function schedulerFailureStopsAndPropagatesRatherThanReturningSuccess(): void
    {
        foreach ([true, false] as $once) {
            $this->handlers(<<<'PHP'
                $enqueuer->channel("default", static fn() => throw new \Windwalker\Queue\Exception\UnrecoverableException("producer failed"));
                $enqueuer->channel("later", static fn() => throw new \LogicException("Later producer must not run"));
                PHP);
            $runner = new ScheduleEnqueuerStub($this->app);
            $output = new BufferedOutput();
            $runner->setMessageOutput($output);
            try {
                $runner->run(0, channels: ['default', 'later'], options: new ScheduleEnqueuerOptions(once: $once, sleep: 0, timeout: 0));
                self::fail('Scheduler must receive the producer exception.');
            } catch (\RuntimeException $e) {
                self::assertSame('producer failed', $e->getMessage());
            }
            self::assertTrue($runner->enqueuer->isStop());
            self::assertStringContainsString('producer failed', $output->fetch());
        }
    }

    #[Test]
    public function schedulerMaintenanceResumesAndForceRunsWhilePaused(): void
    {
        foreach ([false, true] as $force) {
            $this->maintenance = true;
            $runs = 0;
            $this->handlers('$enqueuer->channel("default", static fn() => "produced");');
            $this->config->setDeep('queue.enqueuer.init_scripts', static function (Enqueuer $enqueuer) use (&$runs) {
                $enqueuer->on(AfterEnqueueEvent::class, static function () use (&$runs) {
                    $runs++;
                });
            });
            $states = [];
            $this->config->setDeep('queue.enqueuer.loop_end_scripts', function (Enqueuer $enqueuer) use (&$states, &$runs, $force) {
                $states[] = [$enqueuer->getState(), $runs];
                $this->maintenance = false;
                if (count($states) >= ($force ? 1 : 2)) {
                    $enqueuer->stop('test complete');
                }
            });
            new ScheduleEnqueuerStub($this->app)->run(
                0,
                options: new ScheduleEnqueuerOptions(force: $force, sleep: 0, timeout: 0)
            );
            self::assertSame([Enqueuer::STATE_PAUSE, $force ? 1 : 0], $states[0]);
            self::assertSame(1, $runs);
        }
    }

    #[Test]
    public function schedulerLoopFailurePropagatesAndOptionsAreSnapshotted(): void
    {
        $options = new ScheduleEnqueuerOptions(sleep: 0, timeout: 0);
        $failure = new \RuntimeException('loop listener failed');
        $this->handlers('$enqueuer->channel("default", static fn() => "produced");');
        $this->config->setDeep('queue.enqueuer.init_scripts', static function (Enqueuer $enqueuer) use ($failure, $options) {
            $options->once = true;
            $enqueuer->on(BeforeEnqueueEvent::class, static fn() => throw $failure);
        });
        $runner = new ScheduleEnqueuerStub($this->app);
        try {
            $runner->run(0, options: $options);
            self::fail('Loop failure must propagate.');
        } catch (\RuntimeException $e) {
            self::assertSame($failure, $e);
        }
        self::assertNotNull($runner->enqueuer->pid);
        self::assertFalse($runner->enqueuer->options->once);
    }

    #[Test]
    public function realSchedulerEnqueuerRunsOnceAndInjectsNamedAndTypedDependencies(): void
    {
        $this->app->container->share(LoggerInterface::class, $this->logger);
        $this->handlers(<<<'PHP'
            $enqueuer->channel('default', static function ($controller, \Psr\Log\LoggerInterface $logger) use ($app) {
                $logger->info('scheduler:' . $controller->channel . ':' . $app->config('queue.default'));
                return 'created';
            });
            PHP);
        new ScheduleEnqueuer($this->app)->run(0, options: new ScheduleEnqueuerOptions(once: true));
        self::assertSame('scheduler:default:primary', $this->logger->records[0]['message']);
    }

    #[Test]
    public function schedulerRestoresEnclosingSignalHandlersAfterLoopAndFailure(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('Signal restoration requires PCNTL.');
        }

        $original = [];
        foreach ([SIGINT, SIGTERM, SIGALRM] as $signal) {
            $original[$signal] = pcntl_signal_get_handler($signal);
        }
        $alarm = pcntl_alarm(0);
        $handler = static function () {};

        try {
            foreach (array_keys($original) as $signal) {
                pcntl_signal($signal, $handler);
            }
            foreach ([false, true] as $fail) {
                $this->handlers($fail
                    ? '$enqueuer->channel("default", static fn() => throw new \TypeError("producer error"));'
                    : '$enqueuer->channel("default", static fn() => "produced");');
                try {
                    new ScheduleEnqueuer($this->app)->run(
                        0,
                        options: new ScheduleEnqueuerOptions(sleep: 0, timeout: 1, maxRuns: 1)
                    );
                    self::assertFalse($fail);
                } catch (\TypeError $e) {
                    self::assertTrue($fail);
                    self::assertSame('producer error', $e->getMessage());
                }
                foreach (array_keys($original) as $signal) {
                    self::assertSame($handler, pcntl_signal_get_handler($signal));
                }
                self::assertSame(0, pcntl_alarm(0));
            }
        } finally {
            pcntl_alarm(0);
            foreach ($original as $signal => $previous) {
                pcntl_signal($signal, $previous);
            }
            if ($alarm > 0) {
                pcntl_alarm($alarm);
            }
        }
    }

    private string $directory;

    private Config $config;

    private ConsoleApplication $app;

    private SpyLogger $logger;

    private bool $maintenance = false;

    #[Before]
    protected function prepareApplication(): void
    {
        $this->directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tmp'
            . DIRECTORY_SEPARATOR . 'enqueuer-command-' . uniqid('', true);
        Filesystem::mkdir($this->directory);
        $this->config = new Config([
            '@temp' => $this->directory,
            'queue' => ['default' => 'primary'],
        ]);
        $container = new Container()->setParameters($this->config);
        $container->share(Config::class, $this->config);
        $container->share(PathResolver::class, new PathResolver($this->config));
        $this->logger = new SpyLogger();
        $container->set(LoggerInterface::class, $this->logger, tag: 'system/enqueuer');
        $container->set(LoggerInterface::class, $this->logger, tag: 'system/queue');
        $container->set(Queue::class, new Queue(new NullQueueDriver()), tag: 'primary');
        $maintenance = $this->createStub(MaintenanceManager::class);
        $maintenance->method('isDown')->willReturnCallback(fn() => $this->maintenance);
        $container->share(MaintenanceManager::class, $maintenance);
        $this->app = new ConsoleApplication($container);
        $container->share(ConsoleApplication::class, $this->app);
    }

    #[After]
    protected function removeDirectory(): void
    {
        Filesystem::delete($this->directory);
    }

    private function createIO(
        QueueEnqueuerCommand|QueueWorkerCommand $command,
        array $arguments = [],
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ): IO {
        $definition = new Command('test');
        $command->configure($definition);

        return new IO(
            new ArrayInput($arguments, $definition->getDefinition()),
            new BufferedOutput($verbosity),
            $definition
        );
    }

    private function handlers(string $code): void
    {
        $file = $this->directory . DIRECTORY_SEPARATOR . 'handlers.php';
        file_put_contents($file, "<?php\n" . $code);
        $this->config->setDeep('queue.enqueuer.handlers', [$file]);
    }

    #[Test]
    public function realCommandLoadsHandlersAndInjectsOnlyTheirDeclaredParameters(): void
    {
        $this->handlers(<<<'PHP'
            $enqueuer->channel('default', function ($controller, \Psr\Log\LoggerInterface $logger) {
                $logger->info('handler:' . $controller->channel);
                $controller->enqueue(static fn() => null);
                return 'created';
            });
            PHP);
        $this->app->container->share(LoggerInterface::class, $this->logger);
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())->method('push')
            ->with($this->isInstanceOf(\Closure::class), 0, 'default', [])
            ->willReturn('job-1');
        $this->app->container->set(Queue::class, $queue, tag: 'primary');
        $events = [];
        $this->app->on(BeforeEnqueueEvent::class, static function ($event) use (&$events) {
            $events[] = $event;
        });
        $this->app->on(AfterEnqueueEvent::class, static function ($event) use (&$events) {
            $events[] = $event;
        });
        $command = new QueueEnqueuerCommand($this->app);
        $io = $this->createIO($command, ['--once' => true]);

        self::assertSame(0, $command->execute($io));
        self::assertCount(2, $events);
        self::assertSame($queue, $events[0]->queue);
        self::assertSame('created', $events[1]->result);
        self::assertSame('handler:default', $this->logger->records[0]['message']);
        $text = $io->getOutput()->fetch();
        self::assertStringContainsString('Enqueuer started.', $text);
        self::assertStringContainsString('Connection: primary', $text);
        self::assertStringNotContainsString('Enqueuing Start', $text);
    }

    #[Test]
    public function cliOptionsSelectTheConnectionAndPopulateRunnerOptionsIncludingZeros(): void
    {
        $queue = new Queue(new NullQueueDriver());
        $this->app->container->set(Queue::class, $queue, tag: 'other');
        $command = new EnqueuerCommandStub($this->app);
        $io = $this->createIO($command, [
            'channels' => ['alpha', 'beta'],
            '--connection' => 'other',
            '--once' => true,
            '--force' => true,
            '--memory' => '0',
            '--sleep' => '0.25',
            '--timeout' => '0',
            '--max-runs' => '3',
            '--lifetime' => '10',
            '--shuffle-channels' => true,
            '--stop-when-empty' => true,
        ]);
        self::assertSame(0, $command->execute($io));
        $options = $command->enqueuer->options;
        self::assertSame($queue, $command->enqueuer->getQueue());
        self::assertTrue($options->once);
        self::assertTrue($options->force);
        self::assertSame(0, $options->memoryLimit);
        self::assertSame(0.25, $options->sleep);
        self::assertSame(0, $options->timeout);
        self::assertSame(3, $options->maxRuns);
        self::assertSame(10, $options->lifetime);
        self::assertTrue($options->shuffleChannels);
        self::assertTrue($options->stopWhenEmpty);
        self::assertSame($this->directory . '/queue/enqueuer-restart', $options->restartSignal);
        self::assertNull($command->enqueuer->pid);
        self::assertStringContainsString('Channels: alpha, beta', $io->getOutput()->fetch());
    }

    #[Test]
    public function defaultsRemainUnchangedAndEmptyLoopStopsAfterOneTick(): void
    {
        $command = new EnqueuerCommandStub($this->app);
        $io = $this->createIO($command, ['--once' => true]);
        self::assertSame(0, $command->execute($io));
        $options = $command->enqueuer->options;
        self::assertSame(128, $options->memoryLimit);
        self::assertSame(1.0, $options->sleep);
        self::assertSame(60, $options->timeout);
        self::assertSame(0, $options->maxRuns);
        self::assertSame(0, $options->lifetime);
        self::assertFalse($options->force);
        self::assertFalse($options->shuffleChannels);
        self::assertFalse($options->stopWhenEmpty);
        $ticks = 0;
        $this->app->on(LoopEndEvent::class, static function () use (&$ticks) {
            $ticks++;
        });
        $command = new EnqueuerCommandStub($this->app);
        $io = $this->createIO($command, ['--stop-when-empty' => true, '--timeout' => '0', '--sleep' => '0']);
        self::assertSame(0, $command->execute($io));
        self::assertSame(1, $ticks);
        self::assertStringContainsString('Nothing to enqueue', $io->getOutput()->fetch());
    }

    #[Test]
    public function initScriptsCanDeclareNoParametersOrOnlyTheEnqueuerByType(): void
    {
        $calls = [];
        $this->config->setDeep('queue.enqueuer.init_scripts', [
            static function () use (&$calls) {
                $calls[] = 'no-parameters';
            },
            static function (\Windwalker\Queue\Enqueuer $enqueuer) use (&$calls) {
                $calls[] = $enqueuer;
            },
        ]);
        $command = new EnqueuerCommandStub($this->app);
        self::assertSame(0, $command->execute($this->createIO($command, ['--once' => true])));
        self::assertSame(['no-parameters', $command->enqueuer], $calls);
    }

    #[Test]
    public function allChannelsRunsRegisteredHandlersAndSelectedChannelsUseTheDefaultFallback(): void
    {
        $this->handlers(<<<'PHP'
            $enqueuer->channel('alpha', static fn() => 'alpha');
            $enqueuer->channel('beta', static fn() => 'beta');
            $enqueuer->default(static fn($controller) => 'fallback:' . $controller->channel);
            PHP);
        $results = [];
        $this->app->on(AfterEnqueueEvent::class, static function ($event) use (&$results) {
            $results[] = $event->result;
        });
        $command = new QueueEnqueuerCommand($this->app);
        self::assertSame(0, $command->execute($this->createIO($command, ['--once' => true, '--all-channels' => true])));
        self::assertSame(['alpha', 'beta'], $results);
        $command = new QueueEnqueuerCommand($this->app);
        self::assertSame(0, $command->execute($this->createIO($command, ['--once' => true, 'channels' => ['custom']])));
        self::assertSame(['alpha', 'beta', 'fallback:custom'], $results);
    }

    #[Test]
    public function initAndLoopEndScriptsReceiveOnlyRequestedNamedParametersInOrder(): void
    {
        $this->handlers('$enqueuer->channel("default", static fn() => "done");');
        $order = [];
        $this->config->setDeep('queue.enqueuer.init_scripts', static function ($connection, $event, IOInterface $io) use (&$order) {
            $order[] = ['init', $connection, $event, $io];
        });
        $this->config->setDeep('queue.enqueuer.loop_end_scripts', [
            static function (LoopEndEvent $event, $connection) use (&$order) {
                $order[] = ['end', $connection, $event->runner];
            },
        ]);
        $this->app->on(AfterEnqueueEvent::class, static function () use (&$order) {
            $order[] = ['handler'];
        });
        $command = new EnqueuerCommandStub($this->app);
        $io = $this->createIO($command, ['--max-runs' => '2', '--timeout' => '0', '--sleep' => '0']);

        self::assertSame(0, $command->execute($io));
        self::assertSame(['init', 'handler', 'end', 'handler', 'end'], array_column($order, 0));
        self::assertSame(['init', 'primary', null, $io], $order[0]);
        self::assertSame($command->enqueuer, $order[2][2]);
        self::assertTrue($command->enqueuer->isStop());
    }

    #[Test]
    public function maintenancePausesThenResumesAndForceStillRunsWhilePaused(): void
    {
        $maintenance = &$this->maintenance;
        foreach ([false, true] as $force) {
            $this->maintenance = true;
            $this->handlers('$enqueuer->channel("default", static fn() => "done");');
            $command = new EnqueuerCommandStub($this->app);
            $ticks = 0;
            $states = [];
            $this->config->setDeep('queue.enqueuer.loop_end_scripts', static function ($enqueuer) use (&$ticks, &$states, &$maintenance) {
                $ticks++;
                $states[] = $enqueuer->getState();
                $maintenance = false;
            });
            $io = $this->createIO($command, [
                '--max-runs' => '1', '--timeout' => '0', '--sleep' => '0', '--force' => $force,
            ]);
            self::assertSame(0, $command->execute($io));
            self::assertSame($force ? 1 : 2, $ticks);
            self::assertSame($force ? ['stop'] : ['pause', 'stop'], $states);
        }
    }

    #[Test]
    public function onceFailurePropagatesBeforeApplicationEventForwarding(): void
    {
        $this->handlers('$enqueuer->channel("default", static fn() => throw new \RuntimeException("enqueue failed", 17));');
        $events = [];
        $this->app->on(EnqueueFailureEvent::class, static function ($event) use (&$events) {
            $events[] = $event;
        });
        $command = new QueueEnqueuerCommand($this->app);

        try {
            $command->execute($this->createIO($command, ['--once' => true]));
            self::fail('The original failure must propagate.');
        } catch (\RuntimeException $e) {
            self::assertSame('enqueue failed', $e->getMessage());
            self::assertSame(17, $e->getCode());
        }

        // Local failure listeners throw before dispatch reaches the application dealer.
        self::assertSame([], $events);
        self::assertStringContainsString('Enqueue failed', $this->logger->records[0]['message']);
    }

    #[Test]
    public function loopFailureIsReportedAndTheNextIterationStillRuns(): void
    {
        $this->handlers(<<<'PHP'
            $attempt = 0;
            $enqueuer->channel('default', static function () use (&$attempt) {
                if (++$attempt === 1) {
                    throw new \RuntimeException('first attempt failed');
                }
                return 'recovered';
            });
            PHP);
        $results = [];
        $failures = [];
        $this->app->on(AfterEnqueueEvent::class, static function ($event) use (&$results) {
            $results[] = $event->result;
        });
        $this->app->on(EnqueueFailureEvent::class, static function ($event) use (&$failures) {
            $failures[] = $event;
        });
        $command = new EnqueuerCommandStub($this->app);
        $io = $this->createIO($command, ['--max-runs' => '2', '--timeout' => '0', '--sleep' => '0']);
        self::assertSame(0, $command->execute($io));
        self::assertSame(['recovered'], $results);
        self::assertCount(1, $failures);
        self::assertStringContainsString('first attempt failed', $io->getOutput()->fetch());
    }

    #[Test]
    public function verbosityControlsDebugOutputAndInjectedIoWithoutSuppressingStartup(): void
    {
        $this->handlers(<<<'PHP'
            $enqueuer->channel('default', static function ($controller) {
                $controller->log('handler debug', 'debug');
                return 'done';
            });
            PHP);
        foreach ([
            OutputInterface::VERBOSITY_NORMAL,
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        ] as $verbosity) {
            $command = new QueueEnqueuerCommand($this->app);
            $io = $this->createIO($command, ['--once' => true], $verbosity);
            self::assertSame(0, $command->execute($io));
            $text = $io->getOutput()->fetch();
            self::assertStringContainsString('Enqueuer started.', $text);
            if ($verbosity === OutputInterface::VERBOSITY_VERY_VERBOSE) {
                self::assertSame(1, substr_count($text, 'handler debug'));
                self::assertStringContainsString('Enqueuing Start', $text);
                self::assertStringContainsString('Enqueue End', $text);
            } else {
                self::assertStringNotContainsString('handler debug', $text);
            }
            if ($verbosity === OutputInterface::VERBOSITY_NORMAL) {
                self::assertInstanceOf(NullOutput::class, $this->app->retrieve(IOInterface::class)->getOutput());
            } else {
                self::assertSame($io, $this->app->retrieve(IOInterface::class));
                self::assertSame($io->getOutput(), $this->app->retrieve(Output::class));
            }
        }
    }

    #[Test]
    public function workerEmbeddedEnqueuerStillLoadsHandlersAndImmediatelyProcessesReturnedJob(): void
    {
        $this->handlers(<<<'PHP'
            $enqueuer->channel('producer', static fn() => new \Windwalker\Tests\Core\Queue\Stub\StubInvokableJob());
            PHP);
        $jobs = [];
        $this->app->on(AfterJobRunEvent::class, static function ($event) use (&$jobs) {
            $jobs[] = $event->job;
        });
        $command = new QueueWorkerCommand($this->app);
        $io = $this->createIO($command, ['--once' => true, '--enqueuer' => ['producer']]);
        self::assertSame(0, $command->execute($io));
        self::assertCount(1, $jobs);
        self::assertInstanceOf(\Windwalker\Tests\Core\Queue\Stub\StubInvokableJob::class, $jobs[0]);
        self::assertStringContainsString('Running Enqueuer with queue worker', $io->getOutput()->fetch());
    }
}
