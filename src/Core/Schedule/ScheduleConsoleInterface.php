<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule;

/**
 * Interface ScheduleConsoleInterface
 *
 * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
     */
    public function schedule(Schedule $schedule): void;
}
