<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IO;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\DateTime\MockClock;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\SchedulerClaim;
use Windwalker\Core\Schedule\SchedulerClaimInterface;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Core\Schedule\SchedulerLock;
use Windwalker\Core\Schedule\Exception\ScheduleSkippedException;
use Windwalker\Core\Schedule\ScheduleSkipReason;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\Command\ScheduleRunCommand;
use Windwalker\Core\Schedule\Command\ScheduleWorkerCommand;
use Windwalker\Core\Schedule\Event\ScheduleSkippedEvent;
use Windwalker\Event\EventEmitter;
use Windwalker\Tests\Core\Schedule\Stub\SchedulerRunnerStub;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Filesystem;

#[CoversClass(SchedulerClaim::class)]
class SchedulerClaimTest extends TestCase
{
    private string $directory;

    #[Before]
    protected function prepareDirectory(): void
    {
        $this->directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tmp'
            . DIRECTORY_SEPARATOR . 'scheduler-claim-' . uniqid();
        Filesystem::mkdir($this->directory);
    }

    #[After]
    protected function removeDirectory(): void
    {
        Filesystem::delete($this->directory);
    }

    private function createClaim(?MockClock $clock = null, string $namespace = 'app'): SchedulerClaim
    {
        $paths = $this->createStub(PathResolver::class);
        $paths->method('resolve')->willReturnMap([
            ['@root', 'app'],
            ['@temp', $this->directory],
        ]);

        return new SchedulerClaim(
            $paths,
            namespace: $namespace,
            clock: $clock ?? new MockClock('2026-11-04T00:00:00Z')
        );
    }

    #[Test]
    public function independentInstancesClaimEachUtcMinuteOnlyOnce(): void
    {
        $event = new ScheduleEvent('task');
        $first = $this->createClaim();
        $second = $this->createClaim();

        self::assertTrue($first->claim($event, new DateTimeImmutable('2026-11-04T14:08:01+08:00')));
        self::assertFalse($second->claim($event, new DateTimeImmutable('2026-11-04T06:08:59Z')));
        self::assertTrue($second->claim($event, new DateTimeImmutable('2026-11-04T06:09:00Z')));
        self::assertFalse($first->claim($event, new DateTimeImmutable('2026-11-04T06:08:00Z')));
        self::assertTrue($second->claim(new ScheduleEvent('other'), new DateTimeImmutable('2026-11-04T06:08:00Z')));
        self::assertTrue($this->createClaim(namespace: 'another-app')->claim($event, new DateTimeImmutable('2026-11-04T06:08:00Z')));
    }

    #[Test]
    public function retentionExpiresExactlyTwentyFourHoursAfterAcquisition(): void
    {
        $event = new ScheduleEvent('task');
        $time = new DateTimeImmutable('2026-11-04T06:08:00Z');

        self::assertTrue($this->createClaim()->claim($event, $time));
        self::assertFalse($this->createClaim(new MockClock('2026-11-04T23:59:59Z'))->claim($event, $time));
        self::assertTrue($this->createClaim(new MockClock('2026-11-05T00:00:00Z'))->claim($event, $time));
    }

    #[Test]
    public function corruptClaimsFailClosedAndReleaseTheGuard(): void
    {
        $claim = $this->createClaim();
        $event = new ScheduleEvent('task');
        $time = new DateTimeImmutable('2026-11-04T06:08:00Z');
        self::assertTrue($claim->claim($event, $time));
        $file = glob($this->directory . DIRECTORY_SEPARATOR . 'scheduler-claims' . DIRECTORY_SEPARATOR . '*.json')[0];
        file_put_contents($file, 'invalid json');

        try {
            $claim->claim($event, $time);
            self::fail('Corrupt claims must not allow another execution.');
        } catch (\JsonException) {
            file_put_contents($file, '{}');
        }

        self::assertTrue($this->createClaim()->claim($event, $time));
    }

    #[Test]
    public function applicationBindingsOverrideTheDefaultClaim(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('getType')->willReturn(AppType::CONSOLE);
        $container = new Container()->setParameters(new Config());
        new AppProvider($app)->register($container);
        $custom = $this->createStub(SchedulerClaimInterface::class);
        $container->registerByConfig([
            'bindings' => [
                SchedulerClaimInterface::class => static fn() => $custom,
            ],
        ]);

        self::assertSame($custom, $container->get(SchedulerClaimInterface::class));
    }

    #[Test]
    public function concurrentProcessesHaveExactlyOneClaimWinner(): void
    {
        // The host app autoloader covers Core and the lock package alike.
        $hostAutoload = realpath(env('WINDWALKER_TEST_APP_ROOT')) . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'autoload.php';
        $code = sprintf(
            'require_once %s;'
            . '$paths = new \Windwalker\Core\Application\PathResolver(new \Windwalker\Core\Runtime\Config(%s));'
            . '$claim = new \Windwalker\Core\Schedule\SchedulerClaim($paths);'
            . 'echo $claim->claim(new \Windwalker\Core\Schedule\ScheduleEvent("task"),'
            . 'new \DateTimeImmutable("2026-11-04T06:08:00Z")) ? "won" : "lost";',
            var_export($hostAutoload, true),
            var_export(['@root' => 'app', '@temp' => $this->directory], true)
        );
        $processes = [];

        try {
            for ($i = 0; $i < 6; $i++) {
                $process = new Process([PHP_BINARY, '-r', $code], timeout: 10);
                $processes[] = $process;
                $process->start();
            }

            $results = [];

            foreach ($processes as $process) {
                self::assertSame(0, $process->wait(), $process->getErrorOutput());
                $results[] = $process->getOutput();
            }

            self::assertSame(1, count(array_filter($results, static fn($result) => $result === 'won')));
            self::assertSame(5, count(array_filter($results, static fn($result) => $result === 'lost')));
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
        }
    }

    #[Test]
    public function handlerFailureKeepsItsClaimAfterReleasingTheExecutionLock(): void
    {
        $paths = new PathResolver(new Config(['@root' => 'app', '@temp' => $this->directory]));
        $failure = new \RuntimeException('handler failed');
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('call')->willThrowException($failure);
        $event = new ScheduleEvent('failed', handler: static fn() => null);
        $time = new DateTimeImmutable('2026-11-04T06:08:00Z');
        $service = new ScheduleService($app, new SchedulerLock($paths), new SchedulerClaim($paths));

        try {
            $service->runEvent($event, $time);
            self::fail('The handler must fail.');
        } catch (\RuntimeException $e) {
            self::assertSame($failure, $e);
        }

        $otherService = new ScheduleService($app, new SchedulerLock($paths), new SchedulerClaim($paths));

        try {
            $otherService->runEvent($event, $time);
            self::fail('A failed attempt must retain its claim.');
        } catch (ScheduleSkippedException $e) {
            self::assertSame(ScheduleSkipReason::CLAIMED, $e->reason);
        }
    }

    #[Test]
    public function nonPositiveRetentionIsRejected(): void
    {
        $paths = new PathResolver(new Config(['@root' => 'app', '@temp' => $this->directory]));

        $this->expectException(\InvalidArgumentException::class);
        new SchedulerClaim($paths, retention: 0);
    }

    #[Test]
    public function workerAndRunCommandShareClaimsAndOnlyReportSkipsInVerboseOutput(): void
    {
        $paths = new PathResolver(new Config(['@root' => 'app', '@temp' => $this->directory]));
        $schedule = new Schedule();
        $executions = 0;
        $schedule->cron('task', '* * * * *', static function () use (&$executions) {
            $executions++;
        });
        $dispatcher = new EventEmitter();
        $skips = [];
        $dispatcher->on(ScheduleSkippedEvent::class, static function ($event) use (&$skips) {
            $skips[] = $event;
        });
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('getEventDispatcher')->willReturn($dispatcher);
        $app->method('make')->willReturnMap([[Schedule::class, $schedule]]);
        $app->method('call')->willReturnCallback(static fn($handler) => $handler());
        $service = new ScheduleService($app, new SchedulerLock($paths), new SchedulerClaim($paths));

        $worker = new ScheduleWorkerCommand(new SchedulerRunnerStub($app, $service), $app);
        $definition = new Command('schedule:worker');
        $worker->configure($definition);
        $output = new BufferedOutput();
        $io = new IO(new ArrayInput(['--once' => true], $definition->getDefinition()), $output, $definition);
        self::assertSame(0, $worker->execute($io));
        self::assertStringContainsString('Schedule worker started.', $output->fetch());
        self::assertSame(1, $executions);

        $command = new ScheduleRunCommand($app, $service);
        $definition = new Command('schedule:run');
        $command->configure($definition);
        $input = ['--clock' => '2026-01-01T00:00:59Z'];
        $output = new BufferedOutput();
        self::assertSame(0, $command->execute(new IO(new ArrayInput($input, $definition->getDefinition()), $output, $definition)));
        self::assertSame('', $output->fetch());
        self::assertSame(1, $executions);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        self::assertSame(0, $command->execute(new IO(new ArrayInput($input, $definition->getDefinition()), $output, $definition)));
        self::assertStringContainsString('Schedule skipped: task (Scheduled minute has already been claimed.)', $output->fetch());

        $worker = new ScheduleWorkerCommand(new SchedulerRunnerStub($app, $service), $app);
        $definition = new Command('schedule:worker');
        $worker->configure($definition);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        self::assertSame(0, $worker->execute(new IO(new ArrayInput(['--once' => true], $definition->getDefinition()), $output, $definition)));
        $text = $output->fetch();
        self::assertStringContainsString('Schedule skipped: task (Scheduled minute has already been claimed.)', $text);
        self::assertStringNotContainsString('Schedule completed:', $text);
        self::assertSame(1, $executions);
        self::assertCount(1, $skips);
        self::assertSame(ScheduleSkipReason::CLAIMED, $skips[0]->reason);
    }
}
