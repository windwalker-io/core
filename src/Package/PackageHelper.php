<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Ioc;
use Windwalker\DI\Container;

/**
 * The PackageHelper class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class PackageHelper
{
	/**
	 * registerPackages
	 *
	 * @param array|AbstractPackage  $packages
	 * @param WebApplication|Console $application
	 * @param Container              $container
	 *
	 * @return  void
	 */
	public static function registerPackages($packages, $application, $container)
	{
		$config = Ioc::getConfig();

		foreach ($packages as $package)
		{
			if (is_string($package))
			{
				/** @var \Windwalker\Core\Package\AbstractPackage $package */
				$package = new $package;
			}

			$package->setContainer($container)->initialise();

			if ($application instanceof Console)
			{
				$package->registerCommands($application);
			}

			$pkgConfig = array(
				'name' => $package->getName(),
				'class' => get_class($package),
				'config' => $package::loadConfig()
			);

			$config->set('packages.' . $package->getName(), $pkgConfig);

			$container->set('package.' . $package->getName(), $package);
		}
	}
}
