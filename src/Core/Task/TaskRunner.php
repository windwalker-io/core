<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use Closure;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;
use UnexpectedValueException;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Promise\Exception\UncaughtException;
use Windwalker\Promise\ExtendedPromiseInterface;
use Windwalker\Promise\Promise;
use Windwalker\Promise\Scheduler\ScheduleRunner;
use Windwalker\Promise\SettledResult;

class TaskRunner
{
    /** @var array<string, Closure(): void> */
    private array $pending = [];

    private bool $scheduled = false;

    public function __construct(
        private ApplicationInterface $app,
        private TaskPayload $payload,
        private TaskResultTransportInterface $transport
    ) {
        if (!class_exists(Promise::class)) {
            throw new LogicException('Please install windwalker/promise first: composer require windwalker/promise');
        }
    }

    /**
     * @template T
     * @param   (Closure(): T)|object  $task  A Closure or invokable object; services are injected by the child app.
     * @return  Promise                Resolves to TaskResult<T>.
     */
    public function run(
        object $task,
        TaskRunnerOptions $options = new TaskRunnerOptions()
    ): Promise {
        $timeout = $options->timeout;
        $onOutput = $options->onOutput;
        $captureOutput = $options->captureOutput;
        $name = $options->name;
        $resultTtl = $options->resultTtl;
        $process = null;
        $taskId = bin2hex(random_bytes(16));

        try {
            if ($timeout !== null && (!is_finite($timeout) || $timeout <= 0)) {
                throw new InvalidArgumentException('Task timeout must be positive or null.');
            }

            $input = $this->payload->encodeRequest($task, $taskId, $resultTtl);
            $php = new PhpExecutableFinder()->find();

            if ($php === false) {
                throw new RuntimeException('Unable to locate the PHP CLI executable.');
            }

            $process = $this->app->createProcess([
                $php,
                $this->app->path('@root/windwalker'),
                'task:run',
                '--stdin',
                '--ipc',
                '--no-interaction',
            ]);
            $process->setTimeout($timeout);
            $process->setInput($input);
            // Invoke user callbacks from the poller, not inside Process pipe handling.
            $process->start();
        } catch (Throwable $e) {
            if ($process !== null) {
                $process->stop(0);
            }

            return Promise::reject($e);
        }

        [$promise, $resolve, $reject] = Promise::withResolvers();
        $this->pending[$taskId] = function () use (
            $process,
            $taskId,
            $name,
            $captureOutput,
            $onOutput,
            $resolve,
            $reject
        ): void {
            $error = null;
            $value = null;

            try {
                $running = $process->isRunning();

                foreach ([Process::OUT, Process::ERR] as $type) {
                    $chunk = $type === Process::OUT
                        ? $process->getIncrementalOutput()
                        : $process->getIncrementalErrorOutput();

                    if ($chunk !== '' && $onOutput !== null) {
                        $onOutput($name, $type, $chunk);
                    }
                }

                if (!$captureOutput) {
                    $process->clearOutput();
                    $process->clearErrorOutput();
                }

                $process->checkTimeout();

                if ($running) {
                    return;
                }

                $value = $this->readResult($process, $taskId, $name, $captureOutput);
            } catch (Throwable $e) {
                $process->stop(0);
                $error = $e instanceof TaskExecutionException ? $e : new TaskExecutionException(
                    $e->getMessage(),
                    $process->getExitCode(),
                    $captureOutput ? $process->getErrorOutput() : '',
                    process: $process,
                    taskId: $taskId,
                    name: $name,
                    previous: $e
                );
            }

            unset($this->pending[$taskId]);

            try {
                $this->transport->delete($taskId);
            } catch (Throwable $e) {
                $error = new TaskExecutionException(
                    'Unable to clean up task result: ' . $e->getMessage(),
                    $process->getExitCode(),
                    $captureOutput ? $process->getErrorOutput() : '',
                    process: $process,
                    taskId: $taskId,
                    name: $name,
                    previous: $e
                );
            }

            if (!$captureOutput) {
                $process->clearOutput();
                $process->clearErrorOutput();
            }

            if ($error !== null) {
                $reject($error);
            } else {
                $resolve($value);
            }
        };

        if (!$this->scheduled) {
            $this->scheduled = true;
            $scheduler = ScheduleRunner::getInstance();
            $scheduler->schedule($scheduler->createCursor(), $this->pump(...));
        }

        return $promise;
    }

    /**
     * @template T
     * @param   (Closure(): T)|object  $task
     * @return  TaskResult<T>
     * @throws  Throwable
     */
    public function mustRun(
        object $task,
        TaskRunnerOptions $options = new TaskRunnerOptions()
    ): TaskResult {
        try {
            return $this->run($task, $options)->wait();
        } catch (UncaughtException $e) {
            $reason = $e->getReason();

            throw $reason instanceof Throwable ? $reason : $e;
        }
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param   array<TKey, (Closure(): T)|object>  $tasks
     * @return  array<TKey, SettledResult>          Each value contains TaskResult<T> or a rejection exception.
     */
    public function mustRunMultiple(
        array $tasks,
        TaskRunnerOptions $options = new TaskRunnerOptions()
    ): array {
        return $this->runMultiple($tasks, $options)->wait();
    }

    /**
     * @template TKey of array-key
     * @template T
     * @param   array<TKey, (Closure(): T)|object>  $tasks
     * @return  ExtendedPromiseInterface            Resolves to keyed SettledResult objects containing TaskResult<T> or an exception.
     */
    public function runMultiple(
        array $tasks,
        TaskRunnerOptions $options = new TaskRunnerOptions()
    ): ExtendedPromiseInterface {
        return $tasks === []
            ? Promise::resolve([])
            : Promise::allSettled(
                $this->submit($tasks, $options)
            );
    }

    private function submit(
        array $tasks,
        TaskRunnerOptions $options
    ): array {
        $promises = [];

        // Validate the batch before starting any children.
        foreach ($tasks as $task) {
            if (!is_object($task)) {
                throw new InvalidArgumentException('Tasks must be objects or Closures.');
            }
        }

        foreach ($tasks as $name => $task) {
            $taskOptions = clone $options;
            $taskOptions->name = $name;
            $promises[$name] = $this->run($task, $taskOptions);
        }

        return $promises;
    }

    private function pump(): void
    {
        $uncaught = null;

        try {
            while ($this->pending !== []) {
                foreach ($this->pending as $poll) {
                    try {
                        $poll();
                    } catch (UncaughtException $e) {
                        $uncaught ??= $e;
                    }
                }

                if ($this->pending !== []) {
                    usleep(1000);
                }
            }
        } finally {
            $this->scheduled = false;
        }

        if ($uncaught !== null) {
            throw $uncaught;
        }
    }

    private function readResult(
        Process $process,
        string $taskId,
        string|int|null $name,
        bool $captureOutput
    ): TaskResult {
        $result = $this->transport->receive($taskId);

        if ($result === null) {
            throw new TaskExecutionException(
                'Task process exited without a result.',
                $process->getExitCode(),
                $captureOutput ? $process->getErrorOutput() : '',
                process: $process,
                taskId: $taskId,
                name: $name
            );
        }

        if (($result['version'] ?? null) !== 1 || ($result['taskId'] ?? null) !== $taskId) {
            throw new UnexpectedValueException('Invalid task result identity or protocol version.');
        }

        if (($result['status'] ?? null) === 'rejected') {
            $error = $result['error'] ?? null;

            if (
                !is_array($error)
                || !is_string($error['class'] ?? null)
                || !is_string($error['message'] ?? null)
                || !is_int($error['code'] ?? null)
            ) {
                throw new UnexpectedValueException('Invalid task error result.');
            }

            throw new TaskExecutionException(
                $error['message'],
                $process->getExitCode(),
                $captureOutput ? $process->getErrorOutput() : '',
                $error['class'],
                $error['code'],
                $process,
                $taskId,
                $name
            );
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Task process failed.');
        }

        if (($result['status'] ?? null) !== 'fulfilled' || !array_key_exists('value', $result)) {
            throw new UnexpectedValueException('Invalid task success result.');
        }

        return new TaskResult($taskId, $name, $result['value'], $process, $captureOutput);
    }
}
