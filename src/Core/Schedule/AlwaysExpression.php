<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;

/**
 * The AlwaysExpression class.
 *
 * @since  3.5.6
 */
class AlwaysExpression extends CronExpression
{
    /**
     * Validate a CronExpression.
     *
     * @param string $expression The CRON expression to validate.
     *
     * @return bool True if a valid CRON expression was passed. False if not.
     * @see \Cron\CronExpression::factory
     */
    public static function isValidExpression($expression)
    {
        return $expression === '@always';
    }

    /**
     * Determine if the cron is due to run based on the current date or a
     * specific date.  This method assumes that the current number of
     * seconds are irrelevant, and should be called once per minute.
     *
     * @param string|\DateTimeInterface $currentTime Relative calculation date
     * @param null|string               $timeZone    TimeZone to use instead of the system default
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue($currentTime = 'now', $timeZone = null)
    {
        return true;
    }

    /**
     * Set or change the CRON expression
     *
     * @param string $value CRON expression (e.g. 8 * * * *)
     *
     * @return static
     * @throws \InvalidArgumentException if not a valid CRON expression
     */
    public function setExpression($value)
    {
        return $this;
    }
}
