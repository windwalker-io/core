<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Debug;

use Windwalker\Data\Data;
use Windwalker\String\StringHelper;
use Windwalker\String\Utf8String;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The DebugHelper class.
 *
 * @since  {DEPLOY_VERSION}
 */
class BacktraceHelper
{
	/**
	 * whoCallMe
	 *
	 * @param int $backwards
	 *
	 * @return  array
	 */
	public static function whoCallMe($backwards = 2)
	{
		return static::normalizeBacktrace(debug_backtrace()[$backwards]);
	}

	/**
	 * normalizeBacktrace
	 *
	 * @param   array  $trace
	 *
	 * @return  array
	 */
	public static function normalizeBacktrace($trace)
	{
		$trace = new Data($trace);

		$args = [];

		foreach ($trace['args'] as $arg)
		{
			if (is_array($arg))
			{
				$arg = 'Array';
			}
			elseif (is_object($arg))
			{
				$arg = ReflectionHelper::getShortName($arg);
			}
			elseif (is_string($arg))
			{
				$arg = Utf8String::substr($arg, 0, 10);
				$arg = StringHelper::quote($arg);
			}

			$args[] = $arg;
		}

		return array(
			'file' => $trace['file'] ? $trace['file'] . ' (' . $trace['line'] . ')' : null,
			'function' => ($trace['class'] ? $trace['class'] . $trace['type'] : null) . $trace['function'] .
				sprintf('(%s)', implode(', ', $args))
		);
	}

	/**
	 * normalizeBacktraces
	 *
	 * @param   array  $traces
	 *
	 * @return  array
	 */
	public static function normalizeBacktraces($traces)
	{
		$return = [];

		foreach ($traces as $trace)
		{
			$return[] = static::normalizeBacktrace($trace);
		}

		return $return;
	}
}
