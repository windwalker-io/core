<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Schedule\Command;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleConsoleInterface;

/**
 * The ScheduleCommand class.
 *
 * @since  3.5.3
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
     * Property usage.
     *
     * @var  string
     */
    protected $usage = '%s <cmd>[<...names>]</cmd> <option>[--test]</option>';

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

        $this->addOption('test')
            ->description('Test schedule tasks')
            ->defaultValue(0);
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
        $names = $this->io->getArguments();

        if (!$this->console instanceof ScheduleConsoleInterface) {
            $this->out('Console Application should be ' . ScheduleConsoleInterface::class);

            return true;
        }

        $schedule = $this->console->make(Schedule::class);

        $this->console->schedule($schedule);

        $this->console->triggerEvent('onScheduleRegister', [
            'names' => $names,
            'schedule' => $schedule,
        ]);

        if ($this->getOption('test')) {
            $events = $schedule->getEvents();
        } else {
            $events = $schedule->getDueEvents();
        }

        foreach ($events as $event) {
            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            $event->execute();
        }

        return true;
    }
}
