<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Console;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Registry\Registry;

/**
 * The ConsoleHelper class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ConsoleHelper
{
	/**
	 * loadPackages
	 *
	 * @param string      $env
	 * @param CoreConsole $console
	 *
	 * @return array
	 */
	public static function loadPackages($env = 'dev', CoreConsole $console = null)
	{
		$console = $console ? : Ioc::getApplication();

		return (array) (new Registry)
			->loadFile($console->get('path.etc') . '/app/console.php', 'php')
			->loadFile($console->get('path.etc') . '/app/' . $env . '.php', 'php')
			->get('packages');
	}

	/**
	 * getAllPackagesResolver
	 *
	 * @param string      $env
	 * @param CoreConsole $console
	 *
	 * @return  PackageResolver
	 */
	public static function getAllPackagesResolver($env = 'dev', CoreConsole $console = null)
	{
		$resolver = new PackageResolver($console->container);

		foreach (static::loadPackages($env, $console) as $name => $package)
		{
			$resolver->addPackage($name, $package);
		}

		return $resolver;
	}
}
