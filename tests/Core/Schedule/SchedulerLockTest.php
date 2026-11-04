<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\SchedulerLock;
use Windwalker\Core\Schedule\SchedulerLockInterface;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Filesystem;

#[CoversClass(SchedulerLock::class)]
class SchedulerLockTest extends TestCase
{
    private string $directory;

    #[Before]
    protected function prepareDirectory(): void
    {
        $this->directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tmp'
            . DIRECTORY_SEPARATOR . 'scheduler-lock-' . uniqid();
        Filesystem::mkdir($this->directory);
    }

    #[After]
    protected function removeDirectory(): void
    {
        Filesystem::delete($this->directory);
    }

    private function createFactory(string $root = 'app'): SchedulerLock
    {
        $paths = $this->createStub(PathResolver::class);
        $paths->method('resolve')->willReturnMap([
            ['@root', $root],
            ['@temp', $this->directory],
        ]);

        return new SchedulerLock($paths);
    }

    #[Test]
    public function sameTaskUsesFreshInstancesThatContendUntilRelease(): void
    {
        $factory = $this->createFactory();
        $first = $factory->createLock(new ScheduleEvent('task'));
        $second = $this->createFactory()->createLock(new ScheduleEvent('task'));
        self::assertNotSame($first, $second);

        try {
            self::assertTrue($first->acquire(true));
            self::assertFalse($second->acquire(false));
            $first->release();
            self::assertTrue($second->acquire(true));
        } finally {
            $first->release();
            $second->release();
        }
    }

    #[Test]
    public function differentTasksAndApplicationsDoNotBlockEachOther(): void
    {
        $locks = [
            $this->createFactory()->createLock(new ScheduleEvent('task')),
            $this->createFactory()->createLock(new ScheduleEvent('different')),
            $this->createFactory('another-app')->createLock(new ScheduleEvent('task')),
        ];

        try {
            foreach ($locks as $lock) {
                self::assertTrue($lock->acquire(false));
            }
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }

    }

    #[Test]
    public function applicationBindingsOverrideTheDefaultSchedulerLock(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('getType')->willReturn(AppType::CONSOLE);
        $container = new Container()->setParameters(new Config());
        new AppProvider($app)->register($container);
        $custom = $this->createStub(SchedulerLockInterface::class);
        $container->registerByConfig([
            'bindings' => [
                SchedulerLockInterface::class => static fn() => $custom,
            ],
        ]);

        self::assertSame($custom, $container->get(SchedulerLockInterface::class));
    }

    #[Test]
    public function blockingAcquisitionWaitsForAnotherProcessToRelease(): void
    {
        $lock = new LockFactory(new FlockStore($this->directory))->createLock('blocking-test', ttl: null);
        $autoload = new \ReflectionClass(LockFactory::class)->getFileName();
        $autoload = dirname($autoload, 3) . DIRECTORY_SEPARATOR . 'autoload.php';
        $code = sprintf(
            'require %s; $lock = (new \Symfony\Component\Lock\LockFactory('
            . 'new \Symfony\Component\Lock\Store\FlockStore(%s)))->createLock("blocking-test", null);'
            . 'echo "ready\n"; flush(); $lock->acquire(true); echo "acquired\n"; $lock->release();',
            var_export($autoload, true),
            var_export($this->directory, true)
        );
        $process = new Process([PHP_BINARY, '-r', $code], timeout: 10);

        try {
            self::assertTrue($lock->acquire(true));
            $process->start();
            self::assertTrue($process->waitUntil(static fn($type, $output) => str_contains($output, 'ready')));
            self::assertTrue($process->isRunning());
            self::assertStringNotContainsString('acquired', $process->getOutput());
            $lock->release();
            self::assertSame(0, $process->wait());
            self::assertStringContainsString('acquired', $process->getOutput());
        } finally {
            $lock->release();
            $process->stop();
        }
    }
}
