<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
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
	 * execute
	 *
	 * @return  string
	 */
	public static function execute()
	{
		$name = 'Benchmark-' . uniqid();

		$benchmark = new Benchmark($name);

		$args = func_get_args();

		foreach ($args as $k => $arg)
		{
			$benchmark->addTask('Task-' . ($k + 1), $arg);
		}

		return $benchmark->execute(5000)->render(10);
	}
}
