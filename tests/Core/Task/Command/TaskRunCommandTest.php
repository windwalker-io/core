<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Command;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Windwalker\Console\IO;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Task\Command\TaskRunCommand;
use Windwalker\Core\Task\TaskPayload;
use Windwalker\Tests\Core\Task\Stub\SerializedTask;
use Windwalker\Tests\Core\Task\Stub\ServiceTask;

class TaskRunCommandTest extends TestCase
{
    private string $directory;

    private TaskPayload $payload;

    private ApplicationInterface $app;

    #[Before]
    protected function createDirectory(): void
    {
        $this->directory = dirname(__DIR__, 4) . '/tmp/task-run-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
        $this->app = require dirname(__DIR__) . '/Stub/application.php';
        $this->payload = new TaskPayload($this->app);
    }

    #[After]
    protected function removeDirectory(): void
    {
        foreach (glob($this->directory . '/*') as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([false])]
    #[TestWith([[]])]
    #[TestWith([['items' => [1, 2], 'label' => '<info>raw</info>', 'float' => 1.0]])]
    public function executesSerializedObjectsAndPreservesResults(mixed $value): void
    {
        [$exitCode, $result] = $this->runCommand([
            '--code' => $this->payload->encode(new SerializedTask($value)),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(['version' => 1, 'status' => 'fulfilled', 'value' => $value], $result);
    }

    #[Test]
    public function executesSerializedClosureWithCapturedData(): void
    {
        $value = ['count' => 12];
        [$exitCode, $result] = $this->runCommand([
            '--code' => $this->payload->encode(static fn() => $value),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame($value, $result['value']);
    }

    #[Test]
    public function injectsServicesIntoClosures(): void
    {
        [$exitCode, $result] = $this->runCommand([
            '--code' => $this->payload->encode(
                static fn(ApplicationInterface $app): array => ['injected' => $app->isCliRuntime()]
            ),
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(['injected' => true], $result['value']);
    }

    #[Test]
    public function injectsServicesIntoInvokableObjectsInChildProcess(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/Stub/console.php',
            'task:run',
            '--code=' . $this->payload->encode(new ServiceTask()),
        ]);
        $process->mustRun();

        self::assertSame(
            ['version' => 1, 'status' => 'fulfilled', 'value' => ['injected' => true]],
            json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function rejectsUnresolvableRequiredArguments(): void
    {
        [$exitCode, $result] = $this->runCommand([
            '--code' => $this->payload->encode(static fn(string $missing): string => $missing),
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame('rejected', $result['status']);
        self::assertStringContainsString('missing', $result['error']['message']);
    }

    #[Test]
    public function readsEncodedFileAndWritesIndependentResultFile(): void
    {
        $payloadFile = $this->directory . '/input.txt';
        $resultFile = $this->directory . '/result.json';
        file_put_contents($payloadFile, $this->payload->encode(new SerializedTask('file')) . "\n");

        [$exitCode, $result, $output] = $this->runCommand([
            '--file' => $payloadFile,
            '--result-file' => $resultFile,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertNull($result);
        self::assertSame('', $output);
        self::assertSame(
            ['version' => 1, 'status' => 'fulfilled', 'value' => 'file'],
            json_decode(file_get_contents($resultFile), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function readsStdin(): void
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $this->payload->encode(new SerializedTask('stdin')));
        rewind($stream);

        try {
            [$exitCode, $result] = $this->runCommand(['--stdin' => true], $stream);

            self::assertSame(Command::SUCCESS, $exitCode);
            self::assertSame('stdin', $result['value']);
        } finally {
            fclose($stream);
        }
    }

    #[Test]
    #[TestWith([[]])]
    #[TestWith([['--code' => '', '--stdin' => true]])]
    #[TestWith([['--code' => '', '--file' => 'missing']])]
    #[TestWith([['--code' => '', '--file' => 'missing', '--stdin' => true]])]
    #[TestWith([['--code' => '']])]
    #[TestWith([['--code' => '%%%']])]
    #[TestWith([['--code' => 'YnJva2Vu']])]
    #[TestWith([['--code' => 'Tzo4OiJzdGRDbGFzcyI6MDp7fQ==']])]
    #[TestWith([['--file' => '']])]
    #[TestWith([['--file' => 'missing-task-payload-file']])]
    #[TestWith([['--code' => '', '--result-file' => '']])]
    public function rejectsInvalidInputWithStructuredErrors(array $options): void
    {
        [$exitCode, $result] = $this->runCommand($options);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertSame('rejected', $result['status']);
        self::assertNotEmpty($result['error']['class']);
        self::assertNotEmpty($result['error']['message']);
    }

    #[Test]
    public function reportsTaskExceptionsInResultFile(): void
    {
        $resultFile = $this->directory . '/error.json';
        [$exitCode, , $output] = $this->runCommand([
            '--code' => $this->payload->encode(new SerializedTask(fail: true)),
            '--result-file' => $resultFile,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame('', $output);
        self::assertSame(
            [
                'version' => 1,
                'status' => 'rejected',
                'error' => ['class' => \DomainException::class, 'message' => 'Task failed.', 'code' => 42],
            ],
            json_decode(file_get_contents($resultFile), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function rejectsValuesThatCannotBeEncodedAsJson(): void
    {
        [$exitCode, $result] = $this->runCommand([
            '--code' => $this->payload->encode(new SerializedTask(INF)),
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame('rejected', $result['status']);
        self::assertSame(\JsonException::class, $result['error']['class']);
    }

    #[Test]
    public function writesProtocolEvenWithQuietOutput(): void
    {
        [$exitCode, $result] = $this->runCommand(
            ['--code' => $this->payload->encode(new SerializedTask('quiet'))],
            verbosity: BufferedOutput::VERBOSITY_QUIET
        );

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame('quiet', $result['value']);
    }

    #[Test]
    public function subprocessKeepsDirectOutputSeparateFromResultFile(): void
    {
        $resultFile = $this->directory . '/subprocess.json';
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/Stub/console.php',
            'task:run',
            '--stdin',
            '--result-file=' . $resultFile,
        ]);
        $process->setInput($this->payload->encode(new SerializedTask(['ok' => true], output: true)));
        $process->mustRun();

        self::assertSame("task output\ndirect stdout\n", $process->getOutput());
        self::assertSame("direct stderr\n", $process->getErrorOutput());
        self::assertSame(
            ['version' => 1, 'status' => 'fulfilled', 'value' => ['ok' => true]],
            json_decode(file_get_contents($resultFile), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function subprocessForwardsEchoToStderrInStdoutProtocolMode(): void
    {
        $task = require dirname(__DIR__) . '/Stub/closure.php';
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/Stub/console.php',
            'task:run',
            '--code=' . $this->payload->encode($task),
        ]);
        $process->mustRun();

        self::assertSame("progress\n", $process->getErrorOutput());
        self::assertSame(
            ['version' => 1, 'status' => 'fulfilled', 'value' => ['ok' => true]],
            json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    #[Test]
    public function subprocessReturnsNonzeroWhenResultFileCannotBeWritten(): void
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__) . '/Stub/console.php',
            'task:run',
            '--code=' . $this->payload->encode(new SerializedTask('value')),
            '--result-file=' . $this->directory . '/missing/result.json',
        ]);
        $process->run();

        self::assertNotSame(0, $process->getExitCode());
        self::assertStringContainsString('file_put_contents(', $process->getErrorOutput());
        self::assertSame('', $process->getOutput());
    }

    private function runCommand(
        array $options,
        mixed $stream = null,
        int $verbosity = BufferedOutput::VERBOSITY_NORMAL
    ): array {
        $handler = new TaskRunCommand($this->payload, $this->app);
        $command = new Command('task:run');
        $handler->configure($command);
        $input = new ArrayInput($options, $command->getDefinition());
        $input->setStream($stream);
        $output = new BufferedOutput($verbosity, true);
        $exitCode = $handler->execute(new IO($input, $output, $command));
        $text = $output->fetch();

        return [$exitCode, $text === '' ? null : json_decode($text, true, flags: JSON_THROW_ON_ERROR), $text];
    }
}
