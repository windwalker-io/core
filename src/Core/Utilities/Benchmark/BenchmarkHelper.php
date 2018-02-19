<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Utilities\Benchmark;

use Windwalker\Profiler\Benchmark;

/**
 * The BenchmarkHelper class.
 *
 * @since  2.0
 */
abstract class BenchmarkHelper
{
    /**
     * Property times.
     *
     * @var  integer
     */
    protected static $times = 5000;

    /**
     * Property round.
     *
     * @var  integer
     */
    protected static $round = 10;

    /**
     * execute
     *
     * @return  string
     */
    public static function execute()
    {
        $name = 'Benchmark-' . uniqid();

        $benchmark = new Benchmark($name);

        $args = func_get_args();

        foreach ($args as $k => $arg) {
            $benchmark->addTask('Task-' . ($k + 1), $arg);
        }

        return $benchmark->execute(static::$times)->render(static::$round);
    }

    /**
     * Method to get property Times
     *
     * @return  int
     */
    public static function getTimes()
    {
        return static::$times;
    }

    /**
     * Method to set property times
     *
     * @param   int $times
     *
     * @return  void
     */
    public static function setTimes($times)
    {
        static::$times = $times;
    }

    /**
     * Method to get property Round
     *
     * @return  int
     */
    public static function getRound()
    {
        return static::$round;
    }

    /**
     * Method to set property round
     *
     * @param   int $round
     *
     * @return  void
     */
    public static function setRound($round)
    {
        static::$round = $round;
    }
}
