<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Windwalker\Console\IO;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Database\DatabaseConnectionRecovery;
use Windwalker\Core\Queue\Command\QueueWorkerCommand;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;
use Windwalker\Queue\RunnerOptions;
use Windwalker\Queue\Worker;
use Windwalker\Tests\Core\Queue\Stub\QueueWorkerCommandStub;

#[CoversClass(QueueWorkerCommand::class)]
class QueueWorkerCommandTest extends TestCase
{
    #[Test]
    public function startupConnectionFailureAlsoRequestsShutdown(): void
    {
        $failure = new PDOException('SQLSTATE[HY000] [2002] Connection timed out');
        $shutdown = new \LogicException('Worker terminated.');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $recovery->expects($this->once())->method('shutdown')->with($failure)->willThrowException($shutdown);
        $app = $this->createStub(ConsoleApplication::class);
        $app->method('service')->willReturn($recovery);
        $app->method('config')->willReturn('default');
        $app->method('path')->willReturn('tmp');
        $app->method('retrieve')->willThrowException($failure);
        $command = new QueueWorkerCommand($app);
        $definition = new Command('queue:worker');
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
    public function failedQueuePollingRequestsShutdownWithoutPollingAgain(): void
    {
        $failure = new PDOException('SQLSTATE[HY000] [2002] Connection timed out');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $shutdown = new \LogicException('Worker terminated.');
        $recovery->expects($this->once())->method('shutdown')->with($failure)->willThrowException($shutdown);
        $app = $this->createStub(ConsoleApplication::class);
        $app->method('service')->willReturn($recovery);
        $app->method('config')->willReturn([]);
        $queue = $this->createMock(Queue::class);
        $worker = new Worker($queue, new RunnerOptions(timeout: 0, sleep: 0));
        $queue->expects($this->once())->method('pop')->willThrowException($failure);
        $command = new QueueWorkerCommandStub($app);
        $definition = new Command('queue:worker');
        $command->configure($definition);
        $output = new BufferedOutput();
        $command->prepare(
            $worker,
            new IO(new ArrayInput([], $definition->getDefinition()), $output, $definition)
        );

        try {
            $worker->loop('default');
            self::fail('The worker must terminate.');
        } catch (\LogicException $e) {
            self::assertSame($shutdown, $e);
        }

        self::assertStringContainsString(DatabaseConnectionRecovery::MESSAGE, $output->fetch());
    }

    #[Test]
    public function jobInvokerShutsDownBeforeQueueSettlement(): void
    {
        $failure = new PDOException('MySQL server has gone away');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $shutdown = new \LogicException('Worker terminated.');
        $recovery->expects($this->once())->method('shutdown')->with($failure)->willThrowException($shutdown);
        $app = $this->createStub(ConsoleApplication::class);
        $app->method('service')->willReturn($recovery);
        $app->method('call')->willThrowException($failure);
        $command = new QueueWorkerCommandStub($app);
        $definition = new Command('queue:worker');
        $command->configure($definition);
        $command->prepare(
            new Worker($this->createStub(Queue::class)),
            new IO(new ArrayInput([], $definition->getDefinition()), new BufferedOutput(), $definition)
        );

        try {
            ($command->createInvoker())(new JobController(new QueueMessage()), static fn() => null);
            self::fail('The worker must terminate before normal settlement.');
        } catch (\LogicException $e) {
            self::assertSame($shutdown, $e);
        }
    }

    #[Test]
    public function singleRunInvokerDoesNotRecoverOrRetryTheFailedOperation(): void
    {
        $failure = new PDOException('MySQL server has gone away');
        $recovery = $this->createMock(DatabaseConnectionRecovery::class);
        $recovery->expects($this->never())->method('shutdown');
        $app = $this->createStub(ConsoleApplication::class);
        $app->method('service')->willReturn($recovery);
        $app->method('call')->willThrowException($failure);
        $command = new QueueWorkerCommandStub($app);
        $definition = new Command('queue:worker');
        $command->configure($definition);
        $command->prepare(
            new Worker($this->createStub(Queue::class)),
            new IO(new ArrayInput(['--once' => true], $definition->getDefinition()), new BufferedOutput(), $definition)
        );
        $this->expectException(PDOException::class);

        ($command->createInvoker())(new JobController(new QueueMessage()), static fn() => null);
    }
}
