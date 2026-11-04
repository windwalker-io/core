<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\DateTime\SystemClock;

class SchedulerClaim implements SchedulerClaimInterface
{
    private string $directory;

    private string $namespace;

    private LockFactory $factory;

    public function __construct(
        PathResolver $paths,
        private int $retention = 86400,
        ?string $namespace = null,
        private ClockInterface $clock = new SystemClock()
    ) {
        if ($retention <= 0) {
            throw new InvalidArgumentException('Scheduler claim retention must be positive.');
        }

        $this->namespace = $namespace ?? hash('sha256', $paths->resolve('@root'));
        $this->directory = $paths->resolve('@temp') . DIRECTORY_SEPARATOR . 'scheduler-claims';
        $this->factory = new LockFactory(new FlockStore($this->directory));
    }

    public function claim(ScheduleEvent $event, DateTimeImmutable $scheduledAt): bool
    {
        $resource = 'scheduler:claim:' . $this->namespace . ':' . $event->getName();
        $file = $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $resource) . '.json';
        $guard = $this->factory->createLock($resource, ttl: null);

        // Only the short claim update blocks, never the task execution.
        if (!$guard->acquire(true)) {
            throw new RuntimeException('Unable to acquire scheduler claim guard.');
        }

        $temporary = null;

        try {
            $claims = [];

            if (is_file($file)) {
                $contents = file_get_contents($file);

                if ($contents === false) {
                    throw new RuntimeException('Unable to read scheduler claims: ' . $file);
                }

                $claims = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

                if (!is_array($claims)) {
                    throw new RuntimeException('Invalid scheduler claims: ' . $file);
                }
            }

            $now = $this->clock->now()->getTimestamp();

            foreach ($claims as $minute => $expiresAt) {
                if (!is_int($expiresAt)) {
                    throw new RuntimeException('Invalid scheduler claim expiry: ' . $file);
                }

                if ($expiresAt <= $now) {
                    unset($claims[$minute]);
                }
            }

            $minute = (int) floor($scheduledAt->getTimestamp() / 60);

            if (isset($claims[$minute])) {
                return false;
            }

            $claims[$minute] = $now + $this->retention;
            $contents = json_encode($claims, JSON_THROW_ON_ERROR);
            $temporary = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';

            if (file_put_contents($temporary, $contents) !== strlen($contents) || !rename($temporary, $file)) {
                throw new RuntimeException('Unable to persist scheduler claims: ' . $file);
            }

            return true;
        } finally {
            try {
                if ($temporary !== null && is_file($temporary)) {
                    if (!unlink($temporary)) {
                        throw new RuntimeException('Unable to remove temporary scheduler claims: ' . $temporary);
                    }
                }
            } finally {
                $guard->release();
            }
        }
    }
}
