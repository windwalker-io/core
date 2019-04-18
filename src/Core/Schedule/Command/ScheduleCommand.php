<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule\Command;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleConsoleInterface;

/**
 * The ScheduleCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ScheduleCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'schedule';

    /**
     * Property description.
     *
     * @var string
     */
    protected $description = 'Run schedule';

    /**
     * Initialise command.
     *
     * @return void
     *
     * @since  2.0
     */
    protected function init()
    {
        parent::init();
    }

    /**
     * Execute this command.
     *
     * @return int
     *
     * @since  2.0
     */
    protected function doExecute()
    {
        $args = $this->io->getArguments();

        if (!$this->console instanceof ScheduleConsoleInterface) {
            $this->out('Console Application should be ' . ScheduleConsoleInterface::class);

            return true;
        }

        $schedule = $this->console->make(Schedule::class);

        $this->console->schedule($schedule);

        foreach ($schedule->getDueEvents() as $event) {
            $event->execute();
        }

        return true;
    }
}
