<?php

declare(strict_types=1);

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
     * @param  Schedule  $schedule
     *
     * @return  void
     *
     * @since  3.5.3
     */
    public function schedule(Schedule $schedule): void;
}
