<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

use Windwalker\Core\Queue\ScheduleEnqueuer;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\RunnerOptions;

class ScheduleEnqueuerStub extends ScheduleEnqueuer
{
    public Enqueuer $enqueuer;

    protected function createEnqueuer(string $connection, RunnerOptions $options): Enqueuer
    {
        $original = parent::createEnqueuer($connection, $options);
        $this->enqueuer = new class ($original->getQueue(), $options) extends Enqueuer {
            protected function registerSignals(): void {}
        };
        $ticks = 0;
        $this->enqueuer->on(LoopStartEvent::class, static function () use (&$ticks) {
            if (++$ticks > 10) {
                throw new \LogicException('Scheduler enqueuer test exceeded its loop budget.');
            }
        });

        return $this->enqueuer;
    }
}
