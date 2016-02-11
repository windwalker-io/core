<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Mvc;

use Windwalker\String\StringHelper;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ControllerResolver class.
 *
 * @since  2.1.1
 */
class ControllerResolver extends AbstractClassResolver
{
	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix()
	{
		return 'controller';
	}

	/**
	 * If didn't found any exists class, fallback to default class which in current package..
	 *
	 * @param   string $namespace The package namespace.
	 * @param   string $name      The class task name.
	 *
	 * @return  string  Found class name.
	 */
	protected function getDefaultClass($namespace, $name)
	{
		return $namespace . '\Controller\\' . $name;
	}
}
