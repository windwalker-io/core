<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Queue\ScheduleWorkerOptions;
use Windwalker\Queue\RunnerOptions;

class ScheduleWorkerOptionsTest extends TestCase
{
    #[Test]
    public function defaultsMatchParentRunnerOptionsDefaults(): void
    {
        $options = new ScheduleWorkerOptions();
        $parent = new RunnerOptions();

        self::assertSame($parent->once, $options->once);
        self::assertSame($parent->backoff, $options->backoff);
        self::assertSame($parent->force, $options->force);
        self::assertSame($parent->memoryLimit, $options->memoryLimit);
        self::assertSame($parent->sleep, $options->sleep);
        self::assertSame($parent->tries, $options->tries);
        self::assertSame($parent->timeout, $options->timeout);
        self::assertSame($parent->maxRuns, $options->maxRuns);
        self::assertSame($parent->lifetime, $options->lifetime);
        self::assertSame($parent->shuffleChannels, $options->shuffleChannels);
        self::assertSame($parent->stopWhenEmpty, $options->stopWhenEmpty);
        self::assertSame($parent->restartSignal, $options->restartSignal);
        self::assertSame($parent->controllerFactory, $options->controllerFactory);
        self::assertFalse($options->enqueuer);
    }

    #[Test]
    public function constructorForwardsAllArgumentsToParentAndSetsEnqueuer(): void
    {
        $controllerFactory = static fn() => null;

        $options = new ScheduleWorkerOptions(
            once: true,
            backoff: 5,
            force: true,
            memoryLimit: 256,
            sleep: 2.5,
            tries: 3,
            timeout: 30,
            maxRuns: 10,
            lifetime: 60,
            shuffleChannels: true,
            stopWhenEmpty: true,
            restartSignal: '/tmp/restart',
            controllerFactory: $controllerFactory,
            enqueuer: true
        );

        self::assertTrue($options->once);
        self::assertSame(5, $options->backoff);
        self::assertTrue($options->force);
        self::assertSame(256, $options->memoryLimit);
        self::assertSame(2.5, $options->sleep);
        self::assertSame(3, $options->tries);
        self::assertSame(30, $options->timeout);
        self::assertSame(10, $options->maxRuns);
        self::assertSame(60, $options->lifetime);
        self::assertTrue($options->shuffleChannels);
        self::assertTrue($options->stopWhenEmpty);
        self::assertSame('/tmp/restart', $options->restartSignal);
        self::assertSame($controllerFactory, $options->controllerFactory);
        self::assertTrue($options->enqueuer);
    }

    #[Test]
    public function isInstanceOfRunnerOptions(): void
    {
        self::assertInstanceOf(RunnerOptions::class, new ScheduleWorkerOptions());
    }
}
