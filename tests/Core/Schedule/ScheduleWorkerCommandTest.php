<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IO;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseConnectionRecovery;
use Windwalker\Core\Schedule\Command\ScheduleWorkerCommand;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Event\EventEmitter;
use Windwalker\Tests\Core\Schedule\Stub\SchedulerRunnerStub;

#[CoversClass(ScheduleWorkerCommand::class)]
class ScheduleWorkerCommandTest extends TestCase
{
    #[Test]
    public function scheduleInitializationFailureAlsoRequestsShutdown(): void
    {
        $failure = new PDOException('SQLSTATE[HY000] [2002] Connection timed out');
        $shutdown = new \LogicException('Worker terminated.');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $recovery->expects($this->once())->method('shutdown')->with($failure)->willThrowException($shutdown);
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('service')->willReturn($recovery);
        $app->method('getEventDispatcher')->willReturn(new EventEmitter());
        $service = $this->createStub(ScheduleService::class);
        $service->method('getSchedule')->willThrowException($failure);
        $command = new ScheduleWorkerCommand(new SchedulerRunnerStub($app, $service), $app);
        $definition = new Command('schedule:worker');
        $command->configure($definition);
        $output = new BufferedOutput();
        $io = new IO(new ArrayInput([], $definition->getDefinition()), $output, $definition);

        try {
            $command->execute($io);
            self::fail('Startup must terminate on lost connections.');
        } catch (\LogicException $e) {
            self::assertSame($shutdown, $e);
        }

        self::assertStringContainsString(DatabaseConnectionRecovery::MESSAGE, $output->fetch());
    }

    #[Test]
    public function residentCommandShutsDownBeforeCatchOrTheNextTask(): void
    {
        $failure = new PDOException('SQLSTATE[HY000] [2002] Connection timed out');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $shutdown = new \LogicException('Worker terminated.');
        $recovery->expects($this->once())->method('shutdown')->with($failure)->willThrowException($shutdown);
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('service')->willReturn($recovery);
        $app->method('getEventDispatcher')->willReturn(new EventEmitter());
        $schedule = new Schedule();
        $schedule->cron('failed', '* * * * *', static fn() => throw $failure)
            ->catch(static fn() => self::fail('Must not invoke catch after shutdown.'));
        $executed = 0;
        $schedule->cron('next', '* * * * *', static function () use (&$executed) {
            $executed++;
        });
        $service = $this->createStub(ScheduleService::class);
        $service->method('getSchedule')->willReturn($schedule);
        $service->method('runEvent')->willReturnCallback(static fn($event) => $event->execute());
        $command = new ScheduleWorkerCommand(new SchedulerRunnerStub($app, $service), $app);
        $definition = new Command('schedule:worker');
        $command->configure($definition);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $io = new IO(new ArrayInput(['--max-runs' => '1'], $definition->getDefinition()), $output, $definition);

        try {
            $command->execute($io);
            self::fail('The worker must terminate.');
        } catch (\LogicException $e) {
            self::assertSame($shutdown, $e);
        }

        self::assertSame(0, $executed);
        $text = $output->fetch();
        self::assertStringContainsString(DatabaseConnectionRecovery::MESSAGE, $text);
        self::assertStringNotContainsString('Schedule completed: next', $text);
        self::assertStringNotContainsString('Schedule completed: failed', $text);
    }
}
