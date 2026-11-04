<?php

declare(strict_types=1);

namespace Windwalker\Core\Task\Command;

use ErrorException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Task\TaskPayload;

#[CommandWrapper(description: 'Execute a trusted serialized task and return a JSON result.')]
class TaskRunCommand implements CommandInterface
{
    public function __construct(private TaskPayload $payload, private ApplicationInterface $app) {}

    public function configure(Command $command): void
    {
        $command->addOption('code', 'c', InputOption::VALUE_REQUIRED, 'Signed serialized task payload.');
        $command->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File containing the same encoded payload.');
        $command->addOption('stdin', null, InputOption::VALUE_NONE, 'Read the encoded payload from standard input.');
        $command->addOption('result-file', null, InputOption::VALUE_REQUIRED, 'Write JSON to this file instead of stdout.');
    }

    public function execute(IOInterface $io): int
    {
        $resultFile = $io->getOption('result-file');
        $level = ob_get_level();

        // Keep ordinary PHP output out of the stdout result protocol.
        if ($resultFile === null) {
            ob_start(
                static function (string $output): string {
                    if ($output !== '') {
                        fwrite(STDERR, $output);
                    }

                    return '';
                },
                1
            );
        }

        try {
            $exitCode = Command::INVALID;

            try {
                if ($resultFile === '') {
                    throw new InvalidArgumentException('--result-file must not be empty.');
                }

                $task = $this->payload->decode($this->readPayload($io));
                $exitCode = Command::FAILURE;
                $value = $this->app->call($task);
                $json = json_encode(
                    ['version' => 1, 'status' => 'fulfilled', 'value' => $value],
                    JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
                );
                $exitCode = Command::SUCCESS;
            } catch (Throwable $e) {
                $json = json_encode(
                    [
                        'version' => 1,
                        'status' => 'rejected',
                        'error' => [
                            'class' => $e::class,
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ],
                    ],
                    JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
                );
            }
        } finally {
            while (ob_get_level() > $level) {
                ob_end_flush();
            }
        }

        if ($resultFile !== null && $resultFile !== '') {
            $written = $this->withWarningsAsExceptions(
                static fn() => file_put_contents($resultFile, $json . "\n", LOCK_EX)
            );

            if ($written !== strlen($json) + 1) {
                throw new RuntimeException(sprintf('Unable to write task result to "%s".', $resultFile));
            }
        } else {
            $io->writeln($json, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
        }

        return $exitCode;
    }

    private function readPayload(IOInterface $io): string
    {
        $code = $io->getOption('code');
        $file = $io->getOption('file');
        $stdin = $io->getOption('stdin');

        if ((int) ($code !== null) + (int) ($file !== null) + (int) $stdin !== 1) {
            throw new InvalidArgumentException('Specify exactly one of --code, --file or --stdin.');
        }

        if ($code !== null) {
            return $code;
        }

        if ($file !== null) {
            if ($file === '' || !is_file($file) || !is_readable($file)) {
                throw new InvalidArgumentException(sprintf('Task payload file "%s" is not readable.', $file));
            }

            $payload = $this->withWarningsAsExceptions(static fn() => file_get_contents($file));
        } else {
            $input = $io->getInput();
            $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
            $payload = $this->withWarningsAsExceptions(static fn() => stream_get_contents($stream ?? STDIN));
        }

        if ($payload === false) {
            throw new RuntimeException('Unable to read the task payload.');
        }

        return $payload;
    }

    private function withWarningsAsExceptions(callable $callback): mixed
    {
        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            },
            E_WARNING | E_NOTICE
        );

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
