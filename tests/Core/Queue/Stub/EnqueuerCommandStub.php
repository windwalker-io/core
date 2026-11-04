<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

use Windwalker\Core\Queue\Command\QueueEnqueuerCommand;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;

/**
 * Records the runner created by the real command without installing process-wide signals.
 * Remove when Enqueuer command integration tests no longer require an in-process loop.
 */
class EnqueuerCommandStub extends QueueEnqueuerCommand
{
    public Enqueuer $enqueuer;

    protected function createEnqueuer(?string $connection, RunnerOptions $options): Enqueuer
    {
        $this->enqueuer = new class (
            $this->app->retrieve(Queue::class, tag: $connection),
            $options,
            $this->logger
        ) extends Enqueuer {
            protected function registerSignals(): void {}
        };
        $ticks = 0;
        $this->enqueuer->on(LoopStartEvent::class, static function () use (&$ticks) {
            if (++$ticks > 10) {
                throw new \LogicException('Enqueuer command test exceeded its loop budget.');
            }
        });

        return $this->enqueuer;
    }
}
