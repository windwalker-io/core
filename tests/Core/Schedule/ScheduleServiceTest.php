<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockInterface;
use Windwalker\Core\Schedule\SchedulerLockInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Filesystem\Filesystem;

#[CoversClass(ScheduleService::class)]
class ScheduleServiceTest extends TestCase
{
    private string $tmpDir;

    #[Before]
    protected function prepareTmpDir(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ww-schedule-service-test-' . uniqid('', true);

        Filesystem::mkdir($this->tmpDir);
    }

    #[After]
    protected function removeTmpDir(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    private function createApp(): ApplicationInterface
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('make')
            ->willReturnMap([
                [Schedule::class, new Schedule()],
            ]);

        return $app;
    }

    #[Test]
    public function createScheduleLoadsTasksFromGivenRouteFile(): void
    {
        $route = $this->tmpDir . '/routes.php';

        file_put_contents(
            $route,
            <<<'PHP'
            <?php
            /** @var \Windwalker\Core\Schedule\Schedule $schedule */
            $schedule->cron('demo', '* * * * *', fn () => 'ok');
            PHP
        );

        $service = new ScheduleService($this->createApp(), $this->createStub(SchedulerLockInterface::class));

        $schedule = $service->createSchedule($route);

        self::assertSame(['demo'], array_keys(iterator_to_array($schedule->getEvents())));
    }

    #[Test]
    public function createScheduleLoadsTasksFromMultipleRouteFiles(): void
    {
        $route1 = $this->tmpDir . '/route1.php';
        $route2 = $this->tmpDir . '/route2.php';

        file_put_contents($route1, '<?php $schedule->cron("a", "* * * * *", fn () => null);');
        file_put_contents($route2, '<?php $schedule->cron("b", "* * * * *", fn () => null);');

        $service = new ScheduleService($this->createApp(), $this->createStub(SchedulerLockInterface::class));

        $schedule = $service->createSchedule([$route1, $route2]);

        self::assertSame(['a', 'b'], array_keys(iterator_to_array($schedule->getEvents())));
    }

    #[Test]
    public function createScheduleFallsBackToConfiguredRoutesWhenNoneGiven(): void
    {
        $route = $this->tmpDir . '/configured-routes.php';
        file_put_contents($route, '<?php $schedule->cron("configured", "* * * * *", fn () => null);');

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('make')
            ->willReturnMap([
                [Schedule::class, new Schedule()],
            ]);
        $app->method('config')
            ->willReturnMap([
                ['schedules', [$route]],
            ]);

        $service = new ScheduleService($app, $this->createStub(SchedulerLockInterface::class));

        $schedule = $service->createSchedule();

        self::assertSame(['configured'], array_keys(iterator_to_array($schedule->getEvents())));
    }

    #[Test]
    public function getScheduleMemoizesCreatedScheduleInstance(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(Schedule::class)
            ->willReturn(new Schedule());

        $service = new ScheduleService($app, $this->createStub(SchedulerLockInterface::class));

        $first = $service->getSchedule([]);
        $second = $service->getSchedule([]);

        self::assertSame($first, $second);
    }

    #[Test]
    public function runEventInvokesTheHandlerThroughTheApplicationContainer(): void
    {
        $handler = static fn(string $injected) => $injected;
        $event = new ScheduleEvent('injected', '* * * * *', $handler);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('call')->with($handler)->willReturn('resolved');
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(true);
        $lock->expects($this->once())->method('release');
        $factory = $this->createMock(SchedulerLockInterface::class);
        $factory->expects($this->once())->method('createLock')->with($event)->willReturn($lock);
        $service = new ScheduleService($app, $factory);

        self::assertSame('resolved', $service->runEvent($event));
    }

    #[Test]
    public function handlerFailuresReleaseTheLockAndPropagate(): void
    {
        $failure = new \RuntimeException('handler failed');
        $event = new ScheduleEvent('failure', handler: static fn() => null);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('call')->willThrowException($failure);
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(true);
        $lock->expects($this->once())->method('release');
        $factory = $this->createStub(SchedulerLockInterface::class);
        $factory->method('createLock')->willReturn($lock);
        $service = new ScheduleService($app, $factory);

        $this->expectExceptionObject($failure);
        $service->runEvent($event);
    }

    #[Test]
    public function failedAcquisitionDoesNotInvokeTheHandlerOrReleaseAnotherOwnersLock(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->never())->method('call');
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('acquire')->with(true)->willReturn(false);
        $lock->expects($this->never())->method('release');
        $factory = $this->createStub(SchedulerLockInterface::class);
        $factory->method('createLock')->willReturn($lock);
        $service = new ScheduleService($app, $factory);

        $this->expectException(\Symfony\Component\Lock\Exception\LockConflictedException::class);
        $service->runEvent(new ScheduleEvent('conflict'));
    }

    #[Test]
    public function acquisitionErrorsPropagateWithoutInvokingTheHandler(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->never())->method('call');
        $lock = $this->createMock(LockInterface::class);
        $lock->expects($this->once())->method('acquire')
            ->willThrowException(new \RuntimeException('store unavailable'));
        $lock->expects($this->never())->method('release');
        $factory = $this->createStub(SchedulerLockInterface::class);
        $factory->method('createLock')->willReturn($lock);
        $service = new ScheduleService($app, $factory);

        $this->expectException(\RuntimeException::class);
        $service->runEvent(new ScheduleEvent('error'));
    }
}
