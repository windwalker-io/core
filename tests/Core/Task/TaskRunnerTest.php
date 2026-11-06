<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task;

use DomainException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Task\TaskExecutionException;
use Windwalker\Core\Task\TaskPayload;
use Windwalker\Core\Task\TaskRunner;
use Windwalker\Core\Task\TaskRunnerOptions;
use Windwalker\Core\Task\TaskResult;
use Windwalker\Core\Task\TaskResultTransportInterface;
use Windwalker\Core\Task\CallbackTaskResultTransport;
use Windwalker\Promise\Exception\UncaughtException;
use Windwalker\Promise\Promise;
use Windwalker\Tests\Core\Task\Stub\RunnerTask;
use Windwalker\Tests\Core\Task\Stub\SerializedTask;
use Windwalker\Tests\Core\Task\Stub\ServiceTask;

class TaskRunnerTest extends TestCase
{
    #[Test]
    public function transfersCapturedObjectsAndInjectsNonSerializableServicesInChild(): void
    {
        $entity = (object) ['value' => 'snapshot'];
        $params = ['state' => 1];
        $ids = [12, 34];
        $result = $this->runner->mustRun(
            static function (
                \Windwalker\Tests\Core\Task\Stub\RuntimeTaskService $service,
                ApplicationInterface $app
            ) use ($entity, $params, $ids): array {
                return $service->process($entity, $params, $ids) + ['cli' => $app->isCliRuntime()];
            }
        );

        self::assertSame('snapshot', $result->value['entity']);
        self::assertSame($params, $result->value['params']);
        self::assertSame($ids, $result->value['ids']);
        self::assertTrue($result->value['cli']);
        self::assertNotSame(getmypid(), $result->value['pid']);
    }

    #[Test]
    public function rejectsCapturingNonSerializableServicesBeforeLaunch(): void
    {
        $service = new \Windwalker\Tests\Core\Task\Stub\RuntimeTaskService();

        try {
            $this->runner->mustRun(static function () use ($service): object {
                return $service;
            });
            self::fail('Capturing a non-serializable service must fail.');
        } catch (\LogicException $e) {
            self::assertSame('Runtime services must not be serialized.', $e->getMessage());
            self::assertSame([], $this->processes);
        }
    }

    #[Test]
    public function reportsMissingPromiseDependencyBeforeStartingTasks(): void
    {
        $code = <<<'PHP'
            $loader = require $argv[1];
            $app = require $argv[2];
            $payload = new \Windwalker\Core\Task\TaskPayload($app);
            $transport = new \Windwalker\Core\Task\CallbackTaskResultTransport(
                static function (): void {},
                static fn(): ?array => null,
                static function (): void {}
            );
            $loader->unregister();
            spl_autoload_register(static function (string $class) use ($loader): void {
                if (!str_starts_with($class, 'Windwalker\\Promise\\')) {
                    $loader->loadClass($class);
                }
            });
            try {
                new \Windwalker\Core\Task\TaskRunner($app, $payload, $transport);
                exit(1);
            } catch (\LogicException $e) {
                echo $e->getMessage();
            }
            PHP;
        $process = new Process([
            PHP_BINARY,
            '-r',
            $code,
            realpath(env('WINDWALKER_TEST_APP_ROOT')) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
            __DIR__ . '/Stub/application.php',
        ]);
        $process->mustRun();

        self::assertSame(
            'Please install windwalker/promise first: composer require windwalker/promise',
            $process->getOutput()
        );
    }

    private string $directory;

    private TaskRunner $runner;

    private ApplicationInterface $app;

    private TaskResultTransportInterface $transport;

    /** @var Process[] */
    private array $processes = [];

    #[Before]
    protected function createRunner(): void
    {
        $this->directory = dirname(__DIR__, 3) . '/tmp/task-runner-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
        $sourceApp = require __DIR__ . '/Stub/application.php';
        $app = $this->app = $this->createStub(ApplicationInterface::class);
        $app->method('getSecret')->willReturnCallback($sourceApp->getSecret(...));
        $app->method('path')->willReturnCallback(
            fn(string $path): string => str_replace(
                ['@root', '@temp'],
                [dirname(__DIR__, 3), $this->directory],
                $path
            )
        );
        $app->method('createProcess')->willReturnCallback(
            function (array $command): Process {
                self::assertSame(dirname(__DIR__, 3) . '/windwalker', $command[1]);
                self::assertSame('task:run', $command[2]);
                self::assertContains('--stdin', $command);
                self::assertSame('--ipc', $command[4]);
                $command[1] = __DIR__ . '/Stub/console.php';

                return $this->processes[] = new Process($command, env: ['TASK_TEST_CACHE' => $this->directory]);
            }
        );
        $this->transport = new \Windwalker\Core\Task\CacheTaskResultTransport(
            new \Windwalker\Cache\CachePool(
                new \Windwalker\Cache\Storage\FileStorage($this->directory),
                new \Windwalker\Cache\Serializer\PhpSerializer()
            )
        );
        $this->runner = new TaskRunner($app, new TaskPayload($app), $this->transport);
    }

    #[After]
    protected function removeFiles(): void
    {
        foreach ($this->processes as $process) {
            $process->stop(0);
        }

        foreach (glob($this->directory . '/*') as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith([false])]
    #[TestWith([[]])]
    #[TestWith([['items' => [1, 2], 'nested' => ['ok' => true]]])]
    public function waitsForSingleStructuredResult(mixed $value): void
    {
        $promise = $this->runner->run(new SerializedTask($value));

        self::assertInstanceOf(Promise::class, $promise);
        self::assertTrue($this->processes[0]->isStarted());
        $result = $promise->wait();
        self::assertInstanceOf(TaskResult::class, $result);
        self::assertSame($value, $result->value);
        self::assertSame($result, $promise->wait());
        self::assertSame($this->processes[0], $result->process);
        self::assertSame(0, $result->exitCode);
        self::assertNull($this->transport->receive($result->taskId));
        self::assertSame([], glob($this->directory . '/*.data'));
        self::assertDirectoryDoesNotExist($this->directory . '/tasks');
    }

    #[Test]
    public function runsClosuresAndInjectsChildServices(): void
    {
        self::assertSame(
            ['injected' => true],
            $this->runner->run(new ServiceTask())->wait()->value
        );
        self::assertSame(
            ['ok' => true],
            $this->runner->run(require __DIR__ . '/Stub/closure.php')->wait()->value
        );
    }

    #[Test]
    public function startsTasksConcurrentlyAndComposesWithPromiseAll(): void
    {
        $a = $this->directory . '/a';
        $b = $this->directory . '/b';
        $first = $this->runner->run(new RunnerTask('barrier', 'first', $a, $b));
        $second = $this->runner->run(new RunnerTask('barrier', 'second', $b, $a));

        self::assertCount(2, $this->processes);
        self::assertSame(
            ['first' => 'first', 'second' => 'second'],
            array_map(static fn(TaskResult $r) => $r->value, Promise::all(['first' => $first, 'second' => $second])->wait())
        );
        self::assertNotSame(file_get_contents($a), file_get_contents($b));
    }

    #[Test]
    public function providesBatchSugarPreservingKeysAndEmptyInput(): void
    {
        self::assertSame([], $this->runner->mustRunMultiple([]));
        self::assertSame(
            ['slow' => 'slow', 'fast' => 'fast'],
            array_map(static fn($r) => $r->value->value, $this->runner->mustRunMultiple([
                'slow' => new RunnerTask('sleep', 'slow'),
                'fast' => new SerializedTask('fast'),
            ]))
        );
    }

    #[Test]
    public function allSettledPreservesKeysAndIncludesSuccessAndFailure(): void
    {
        self::assertSame([], $this->runner->runMultiple([])->wait());
        $results = $this->runner->runMultiple([
            'slow' => new RunnerTask('sleep', null),
            'failed' => new SerializedTask(fail: true),
            'invalid' => new \stdClass(),
            7 => new SerializedTask(false),
        ])->wait();

        self::assertSame(['slow', 'failed', 'invalid', 7], array_keys($results));
        self::assertSame(\Windwalker\Promise\Enum\PromiseState::FULFILLED, $results['slow']->status);
        self::assertNull($results['slow']->value->value);
        self::assertSame(\Windwalker\Promise\Enum\PromiseState::REJECTED, $results['failed']->status);
        self::assertInstanceOf(TaskExecutionException::class, $results['failed']->value);
        self::assertSame('Task failed.', $results['failed']->value->getMessage());
        self::assertSame(\Windwalker\Promise\Enum\PromiseState::REJECTED, $results['invalid']->status);
        self::assertInstanceOf(\InvalidArgumentException::class, $results['invalid']->value);
        self::assertSame(\Windwalker\Promise\Enum\PromiseState::FULFILLED, $results[7]->status);
        self::assertFalse($results[7]->value->value);
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function synchronousBatchReturnsFailuresWithoutAbandoningSiblings(): void
    {
        $results = $this->runner->mustRunMultiple([
            'failed' => new SerializedTask(fail: true),
            'success' => new RunnerTask('sleep', 'done'),
        ]);
        $reason = $results['failed']->value;

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame('Task failed.', $reason->getMessage());
        self::assertSame('done', $results['success']->value->value);
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function synchronousSingleReturnsResult(): void
    {
        self::assertSame(['ok' => true], $this->runner->mustRun(new SerializedTask(['ok' => true]))->value);
    }

    #[Test]
    public function synchronousSingleThrowsUnderlyingTaskException(): void
    {
        $this->expectException(TaskExecutionException::class);
        $this->expectExceptionMessage('Task failed.');
        $this->runner->mustRun(new SerializedTask(fail: true));
    }

    #[Test]
    public function synchronousSingleThrowsOriginalValidationException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->runner->mustRun(new \stdClass());
    }

    #[Test]
    public function supportsThenAndRecovery(): void
    {
        self::assertSame(
            6,
            $this->runner->run(new SerializedTask(3))->then(static fn(TaskResult $r): int => $r->value * 2)->wait()
        );
        self::assertSame(
            'recovered',
            $this->runner->run(new SerializedTask(fail: true))
                ->catch(static fn(TaskExecutionException $e): string => 'recovered')
                ->wait()
        );
    }

    #[Test]
    public function preservesRemoteErrorDetails(): void
    {
        $reason = $this->rejection($this->runner->run(new SerializedTask(fail: true)));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame(DomainException::class, $reason->remoteClass);
        self::assertSame('Task failed.', $reason->getMessage());
        self::assertSame(42, $reason->getCode());
        self::assertSame(1, $reason->exitCode);
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function drainsLargeOutputWithoutCorruptingResults(): void
    {
        self::assertSame(
            ['noisy' => 1, 'other' => 2],
            array_map(static fn($r) => $r->value->value, $this->runner->mustRunMultiple([
                'noisy' => new RunnerTask('output', 1),
                'other' => new SerializedTask(2),
            ]))
        );
    }

    #[Test]
    public function rejectsMissingResultEvenWithSuccessfulExitCode(): void
    {
        $reason = $this->rejection($this->runner->run(new RunnerTask('exit')));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame(0, $reason->exitCode);
        self::assertStringContainsString('without a result', $reason->getMessage());
    }

    #[Test]
    public function enforcesTimeoutAndCleansUp(): void
    {
        $reason = $this->rejection($this->runner->run(new RunnerTask('sleep'), new TaskRunnerOptions(timeout: 0.05)));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertInstanceOf(ProcessTimedOutException::class, $reason->getPrevious());
        self::assertFalse($this->processes[0]->isRunning());
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    #[TestWith(['broken', \JsonException::class])]
    #[TestWith(['{"version":2,"status":"fulfilled","value":1}', \UnexpectedValueException::class])]
    #[TestWith(['{"version":1,"status":"fulfilled"}', \UnexpectedValueException::class])]
    #[TestWith(['{"version":1,"status":"rejected","error":{}}', \UnexpectedValueException::class])]
    public function rejectsMalformedResults(string $json, string $exceptionClass): void
    {
        $transport = new CallbackTaskResultTransport(
            $this->transport->publish(...),
            static function (string $id) use ($json): array {
                $result = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                $result['taskId'] = $id;

                return $result;
            },
            $this->transport->delete(...)
        );
        $runner = new TaskRunner($this->app, new TaskPayload($this->app), $transport);
        $reason = $this->rejection($runner->run(new SerializedTask()));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertInstanceOf($exceptionClass, $reason->getPrevious());
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function aggregateFailuresStillCleanUpSiblings(): void
    {
        $aggregate = Promise::all([
            $this->runner->run(new SerializedTask(fail: true)),
            $this->runner->run(new RunnerTask('sleep', 'done')),
        ]);
        $this->rejection($aggregate);

        self::assertSame([], glob($this->directory . '/*.data'));
        self::assertFalse($this->processes[1]->isRunning());
    }

    #[Test]
    public function rejectsInvalidTasksWithoutLaunchingProcesses(): void
    {
        $reason = $this->rejection($this->runner->run(new \stdClass()));

        self::assertInstanceOf(\InvalidArgumentException::class, $reason);
        self::assertSame([], $this->processes);
    }

    #[Test]
    #[TestWith([0.0])]
    #[TestWith([-1.0])]
    #[TestWith([INF])]
    #[TestWith([NAN])]
    public function rejectsInvalidTimeouts(float $timeout): void
    {
        $reason = $this->rejection($this->runner->run(new SerializedTask(), new TaskRunnerOptions(timeout: $timeout)));

        self::assertInstanceOf(\InvalidArgumentException::class, $reason);
        self::assertSame([], $this->processes);
    }

    #[Test]
    public function canDisableTimeout(): void
    {
        self::assertSame('done', $this->runner->run(
            new SerializedTask('done'),
            new TaskRunnerOptions(timeout: null)
        )->wait()->value);
        self::assertNull($this->processes[0]->getTimeout());
    }

    #[Test]
    public function batchOptionsAreReusableAndDoNotLeakTaskNames(): void
    {
        $options = new TaskRunnerOptions(timeout: null, name: 'single', resultTtl: 123);
        $results = $this->runner->mustRunMultiple([
            'first' => new SerializedTask(1),
            7 => new SerializedTask(2),
        ], $options);

        self::assertSame('first', $results['first']->value->name);
        self::assertSame(7, $results[7]->value->name);
        self::assertSame('single', $options->name);
        self::assertNull($results['first']->value->process->getTimeout());

        $promise = $this->runner->run(new SerializedTask(3), $options);
        $options->name = 'changed';
        self::assertSame('single', $promise->wait()->name);
    }

    #[Test]
    public function rejectsInvalidResultTtlInOptions(): void
    {
        $reason = $this->rejection($this->runner->run(
            new SerializedTask(),
            new TaskRunnerOptions(resultTtl: 0)
        ));

        self::assertInstanceOf(\InvalidArgumentException::class, $reason);
        self::assertSame([], $this->processes);
    }

    #[Test]
    public function retainsFullOutputOnFailure(): void
    {
        $reason = $this->rejection($this->runner->run(new RunnerTask('output-fail')));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame(str_repeat('e', 200000), $reason->stderr);
        self::assertSame($this->processes[0], $reason->process);
        self::assertSame(str_repeat('o', 200000), $reason->process->getOutput());
        self::assertSame('Noisy task failed.', $reason->getMessage());
    }

    #[Test]
    public function rejectsLaunchFailuresAndCleansUp(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturnCallback(
            fn(string $path): string => str_replace(['@root', '@temp'], $this->directory, $path)
        );
        $app->method('createProcess')->willReturn(new Process([$this->directory . '/missing-php']));
        $runner = new TaskRunner($app, new TaskPayload(require __DIR__ . '/Stub/application.php'), $this->transport);

        $this->rejection($runner->run(new SerializedTask()));
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function streamsOutputByTaskKeyWithOptionalCapture(bool $capture): void
    {
        $chunks = [];
        $results = $this->runner->mustRunMultiple(
            [
                'named' => new RunnerTask('output', 1),
                7 => new RunnerTask('output', 2),
            ],
            new TaskRunnerOptions(
                onOutput: static function (string|int $name, string $type, string $chunk) use (&$chunks): void {
                    $chunks[$name][$type] = ($chunks[$name][$type] ?? '') . $chunk;
                },
                captureOutput: $capture
            )
        );

        foreach (['named', 7] as $name) {
            $result = $results[$name]->value;
            self::assertSame($name, $result->name);
            self::assertSame(str_repeat('o', 200000), $chunks[$name][Process::OUT]);
            self::assertSame(str_repeat('e', 200000), $chunks[$name][Process::ERR]);
            self::assertSame($capture ? $chunks[$name][Process::OUT] : '', $result->output);
            self::assertSame($capture ? $chunks[$name][Process::ERR] : '', $result->errorOutput);
            self::assertSame($capture, $result->captureOutput);
        }
    }

    #[Test]
    public function callbackFailureStopsOnlyItsTaskAndCleansSiblings(): void
    {
        $results = $this->runner->runMultiple(
            ['noisy' => new RunnerTask('output'), 'sibling' => new RunnerTask('sleep', 'done')],
            new TaskRunnerOptions(
                onOutput: static function (): never {
                    throw new DomainException('Output callback failed.');
                }
            )
        )->wait();

        $reason = $results['noisy']->value;
        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame('Output callback failed.', $reason->getMessage());
        self::assertInstanceOf(DomainException::class, $reason->getPrevious());
        self::assertFalse($reason->process->isRunning());
        self::assertSame('done', $results['sibling']->value->value);
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function streamOnlyFailureDoesNotRetainOutput(): void
    {
        $reason = $this->rejection($this->runner->run(
            new RunnerTask('output-fail'),
            new TaskRunnerOptions(captureOutput: false)
        ));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertSame('', $reason->stderr);
        self::assertSame('', $reason->process->getOutput());
        self::assertSame('', $reason->process->getErrorOutput());
    }

    #[Test]
    public function missingEvictedResultsAreNotSuccessfulNull(): void
    {
        $transport = new CallbackTaskResultTransport(
            $this->transport->publish(...),
            static fn(): ?array => null,
            $this->transport->delete(...)
        );
        $runner = new TaskRunner($this->app, new TaskPayload($this->app), $transport);
        $reason = $this->rejection($runner->run(new SerializedTask(null)));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertStringContainsString('without a result', $reason->getMessage());
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    #[Test]
    public function cleanupFailuresRejectExplicitly(): void
    {
        $transport = new CallbackTaskResultTransport(
            $this->transport->publish(...),
            $this->transport->receive(...),
            function (string $id): never {
                $this->transport->delete($id);
                throw new \RuntimeException('Delete failed.');
            }
        );
        $runner = new TaskRunner($this->app, new TaskPayload($this->app), $transport);
        $reason = $this->rejection($runner->run(new SerializedTask('done')));

        self::assertInstanceOf(TaskExecutionException::class, $reason);
        self::assertStringContainsString('Delete failed.', $reason->getMessage());
        self::assertSame([], glob($this->directory . '/*.data'));
    }

    private function rejection(\Windwalker\Promise\PromiseInterface $promise): \Throwable
    {
        try {
            $promise->wait();
        } catch (UncaughtException $e) {
            self::assertInstanceOf(\Throwable::class, $e->getReason());

            return $e->getReason();
        }

        self::fail('Expected a rejected promise.');
    }
}
