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
use Windwalker\DI\Container;
use Windwalker\Ioc;
use Windwalker\Registry\Registry;

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
	 * @param   WebApplication|Console  $application
	 * @param   array|AbstractPackage   $packages
	 * @param   Container               $container
	 *
	 * @return  void
	 */
	public static function registerPackages($application, $packages, $container = null)
	{
		$container = $container ? : Ioc::getContainer();

		$config = $container->get('system.config');

		foreach ($packages as $alias => $package)
		{
			if (is_string($package))
			{
				/** @var \Windwalker\Core\Package\AbstractPackage $package */
				$package = new $package;
			}

			// If we set custom name to package, use this as alias.
			if (!is_numeric($alias))
			{
				$package->setName($alias);
			}

			// Get package identify name.
			$name = $package->getName();

			// Get global config to override package config
			$pkgConfig = new Registry($package->loadConfig());

			// Legacy to override package config from global config
			$pkgConfig->loadObject($config->get('package.' . $name, array()));

			// Override package config from etc
			$file = $config->get('path.etc') . '/' . $name . '/config.yml';

			if (is_file($file))
			{
				$pkgConfig->loadFile($file);
			}

			$pkgConfig = array(
				'name' => $name,
				'class' => get_class($package),
				'config' => $pkgConfig->getRaw()
			);

			$config->set('package.' . $name, (object) $pkgConfig);

			// Set container and init it
			$subContainer = $container->createChild($name);

			$package->setContainer($subContainer)->initialise();

			// If in Console mode, register commands.
			if ($application instanceof Console)
			{
				$package->registerCommands($application);
			}

			$container->share('package.' . $name, $package);

			$subContainer->alias('package', 'package.' . $name);
		}
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public static function getPackage($name)
	{
		$key = 'package.' . strtolower($name);

		if (Ioc::exists($key))
		{
			return Ioc::get($key);
		}

		return null;
	}

	/**
	 * getPath
	 *
	 * @param string $package
	 *
	 * @return  string
	 */
	public static function getPath($package)
	{
		return Ioc::getPackage($package)->getDir();
	}

	/**
	 * getClassName
	 *
	 * @param string $package
	 *
	 * @return  string
	 */
	public static function getClassName($package)
	{
		return Ioc::getConfig()->get('package.' . $package . '.class');
	}

	/**
	 * getConfig
	 *
	 * @param string $package
	 *
	 * @return  Registry
	 */
	public static function getConfig($package)
	{
		return Ioc::getConfig()->extract('package.' . $package);
	}

	/**
	 * has
	 *
	 * @param string $package
	 *
	 * @return  boolean
	 */
	public static function has($package)
	{
		return Ioc::exists('package.' . $package);
	}
}
