<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

use Psr\Log\NullLogger;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Queue\Command\QueueWorkerCommand;
use Windwalker\Queue\Worker;

/**
 * Exposes worker wiring for connection-recovery regressions without starting a CLI application.
 * Remove when QueueWorkerCommand's recovery behavior no longer needs these tests.
 */
class QueueWorkerCommandStub extends QueueWorkerCommand
{
    public function prepare(Worker $worker, IOInterface $io): void
    {
        $this->io = $io;
        $this->logger = new NullLogger();
        $worker->setInvoker($this->createInvoker());
        $this->listenToWorker($worker, $io, 'default');
    }
}
