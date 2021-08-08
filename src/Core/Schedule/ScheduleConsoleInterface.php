<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    MIT
 */

namespace Windwalker\Core\Schedule;

/**
 * Interface ScheduleConsoleInterface
 *
 * @since  3.5.3
 */
interface ScheduleConsoleInterface
{
    /**
     * schedule
     *
     * @param Schedule $schedule
     *
     * @return  void
     *
     * @since  3.5.3
     */
    public function schedule(Schedule $schedule): void;
}
