<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Queue\Command\QueueEnqueuerCommand;
use Windwalker\Core\Queue\ScheduleEnqueuer;
use Windwalker\Core\Queue\ScheduleEnqueuerOptions;

class ScheduleEnqueuerTest extends TestCase
{
    #[Test]
    public function buildsSupportedOptionsAndForwardsBothOutputStreamsWithoutExtraNewlines(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $command = null;
        $output = null;
        $app->expects($this->once())->method('mustRunProcess')
            ->willReturnCallback(function ($cmd, $options, $callback) use (&$command, &$output) {
                $command = $cmd;
                $output = $callback;

                return $this->createStub(Process::class);
            });
        $options = new ScheduleEnqueuerOptions(
            once: true,
            force: true,
            memoryLimit: 256,
            sleep: 0.5,
            timeout: 90,
            maxRuns: 12,
            shuffleChannels: true,
            stopWhenEmpty: true
        );
        $enqueuer = new ScheduleEnqueuer($app);
        $enqueuer->runByCommand(297, 'redis', ['default', 'high priority'], $options);

        if (!is_string($command) || !is_callable($output)) {
            throw new \LogicException('Process command and output callback were not captured.');
        }

        self::assertStringStartsWith('php windwalker queue:enqueuer ', $command);
        foreach ([
            '--lifetime' => '297',
            '--memory' => '256',
            '--sleep' => '0.5',
            '--timeout' => '90',
            '--max-runs' => '12',
            '--connection' => 'redis',
        ] as $name => $value) {
            self::assertStringContainsString($name . ' ' . escapeshellarg($value), $command);
        }
        foreach (['--once', '--force', '--shuffle-channels', '--stop-when-empty'] as $flag) {
            self::assertStringContainsString($flag, $command);
        }
        self::assertStringContainsString(escapeshellarg('high priority'), $command);

        $events = [];
        $enqueuer->onMessages(static function (MessageOutputEvent $event) use (&$events) {
            $events[] = $event;
        });
        $output('out', "hello\n");
        $output('err', 'failure');
        self::assertSame(["hello\n", 'failure'], array_map(static fn($event) => $event->messages, $events));
        self::assertFalse($events[0]->newLine);
        self::assertFalse($events[1]->newLine);
        self::assertSame(0.5, $options->sleep);
    }

    #[Test]
    public function zeroValuesAreForwardedAndFalseFlagsAndUnspecifiedConnectionAreOmitted(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('mustRunProcess')
            ->with(
                'php windwalker queue:enqueuer --lifetime ' . escapeshellarg('0')
                . ' --memory ' . escapeshellarg('0')
                . ' --sleep ' . escapeshellarg('0')
                . ' --timeout ' . escapeshellarg('0')
                . ' --max-runs ' . escapeshellarg('0')
            )->willReturn($this->createStub(Process::class));

        new ScheduleEnqueuer($app)->runByCommand(
            0,
            options: new ScheduleEnqueuerOptions(memoryLimit: 0, sleep: 0, timeout: 0)
        );
    }

    #[Test]
    public function generatedArgumentsMatchTheEnqueuerCommandDefinition(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('mustRunProcess')
            ->willReturnCallback(function ($cmd) {
                $definition = new Command();
                new QueueEnqueuerCommand($this->createStub(ConsoleApplication::class))->configure($definition);
                $input = new StringInput(substr($cmd, strlen('php windwalker queue:enqueuer ')));
                $input->bind($definition->getDefinition());
                $input->validate();
                self::assertSame(['*'], $input->getArgument('channels'));
                self::assertSame('297', $input->getOption('lifetime'));
                self::assertSame('2', $input->getOption('sleep'));

                return $this->createStub(Process::class);
            });

        new ScheduleEnqueuer($app)->runByCommand(297, channels: '*', options: new ScheduleEnqueuerOptions(sleep: 2));
    }

    #[Test]
    public function processFailuresPropagateToTheScheduler(): void
    {
        $failure = new \RuntimeException('process failed');
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('mustRunProcess')->willThrowException($failure);

        $this->expectExceptionObject($failure);
        new ScheduleEnqueuer($app)->runByCommand(297);
    }

    #[Test]
    public function invalidInputDoesNotStartAProcess(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->never())->method('mustRunProcess');
        $enqueuer = new ScheduleEnqueuer($app);

        foreach ([
            [-1, null, null, new ScheduleEnqueuerOptions()],
            [0, null, null, new ScheduleEnqueuerOptions(sleep: INF)],
            [0, null, null, new ScheduleEnqueuerOptions(sleep: -1)],
            [0, null, null, new ScheduleEnqueuerOptions(memoryLimit: -1)],
            [0, null, null, new ScheduleEnqueuerOptions(timeout: -1)],
            [0, null, null, new ScheduleEnqueuerOptions(maxRuns: -1)],
            [0, '', null, new ScheduleEnqueuerOptions()],
            [0, null, '--once', new ScheduleEnqueuerOptions()],
            [0, null, [123], new ScheduleEnqueuerOptions()],
        ] as $arguments) {
            try {
                $enqueuer->runByCommand(...$arguments);
                self::fail('Invalid input must throw.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

    }

    #[Test]
    public function invalidRunInputIsRejectedBeforeResolvingQueueServices(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->never())->method('retrieve');
        $runner = new ScheduleEnqueuer($app);

        foreach ([
            [-1, null, 'default', new ScheduleEnqueuerOptions()],
            [0, '', 'default', new ScheduleEnqueuerOptions()],
            [0, null, [123], new ScheduleEnqueuerOptions()],
            [0, null, '', new ScheduleEnqueuerOptions()],
            [0, null, 'default', new ScheduleEnqueuerOptions(sleep: NAN)],
        ] as $arguments) {
            try {
                $runner->run(...$arguments);
                self::fail('Invalid run input must throw.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
