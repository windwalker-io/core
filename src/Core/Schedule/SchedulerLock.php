<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Windwalker\Core\Application\PathResolver;

class SchedulerLock implements SchedulerLockInterface
{
    private LockFactory $factory;

    private string $namespace;

    public function __construct(PathResolver $paths, ?string $namespace = null)
    {
        $this->namespace = $namespace ?? hash('sha256', $paths->resolve('@root'));
        $this->factory = new LockFactory(
            new FlockStore($paths->resolve('@temp') . DIRECTORY_SEPARATOR . 'scheduler-locks')
        );
    }

    public function createLock(ScheduleEvent $event): LockInterface
    {
        return $this->factory->createLock(
            'scheduler:' . $this->namespace . ':' . $event->getName(),
            ttl: null
        );
    }
}
