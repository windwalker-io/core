<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Utilities\Benchmark;

use Windwalker\Profiler\Benchmark;

/**
 * The BenchmarkHelper class.
 * 
 * @since  {DEPLOY_VERSION}
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
