<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Controller;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;

/**
 * The ControllerResolver class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ControllerResolver
{
	/**
	 * getController
	 *
	 * @param   AbstractPackage|string  $package
	 * @param   string                  $controller
	 *
	 * @return  mixed
	 */
	public static function getController($package, $controller)
	{
		if (!$package instanceof AbstractPackage)
		{
			$package = PackageHelper::getPackage($package);
		}

		$class = $controller;

		if (!class_exists($class))
		{
			$namespace = MvcHelper::getPackageNamespace($package, 1);

			$class = $namespace . '\Controller\\' . $controller;
		}

		if (!class_exists($class))
		{
			throw new \UnexpectedValueException('Controller: ' . $controller . ' not found.', 404);
		}

		return $class;
	}
}
