<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ViewResolver class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ViewResolver extends AbstractClassResolver
{
	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix()
	{
		return 'view';
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
		return $namespace . '\View\\' . $name;
	}
}
