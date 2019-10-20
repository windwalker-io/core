<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Queue\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Queue\Command\Queue;

/**
 * The QueueCommand class.
 *
 * @since  3.2
 */
class QueueCommand extends Command
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'queue';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Queue management.';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addCommand(Queue\WorkerCommand::class);
        $this->addCommand(Queue\TableCommand::class);
        $this->addCommand(Queue\FailedTableCommand::class);
        $this->addCommand(Queue\RetryCommand::class);
        $this->addCommand(Queue\RestartCommand::class);
        $this->addCommand(Queue\RemoveFailedCommand::class);
    }
}
