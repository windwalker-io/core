<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\Core\Application\WebApplication;
use Windwalker\DI\Container;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;

/**
 * The PackageHelper class.
 * 
 * @since  2.0
 */
abstract class PackageHelper
{
	/**
	 * registerPackages
	 *
	 * @param   array|AbstractPackage[] $packages
	 * @param   Container               $container
	 *
	 * @return  void
	 */
	public static function registerPackages($packages, Container $container = null)
	{
		$container = $container ? : Ioc::factory();

		foreach ($packages as $alias => $package)
		{
			static::addPackage($alias, $package, $container);
		}
	}

	/**
	 * addPackage
	 *
	 * @param string                  $alias
	 * @param string|AbstractPackage  $package
	 * @param Container               $container
	 *
	 * @return  AbstractPackage
	 */
	public static function addPackage($alias, $package, Container $container = null)
	{
		$container = $container ? : Ioc::factory();
		$config = $container->get('system.config');

		if (is_string($package))
		{
			if (!class_exists($package))
			{
				throw new \InvalidArgumentException($package . ' is not a valid class.');
			}

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

		$application = $container->get('system.application');

		// If in Console mode, register commands.
		if ($application instanceof Console)
		{
			$package->registerCommands($application);
		}

		$container->share('package.' . $name, $package);

		$subContainer->alias('package', 'package.' . $name);

		return $package;
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public static function getPackage($name, Container $container = null)
	{
		$container = $container ? : Ioc::factory();

		$key = 'package.' . strtolower($name);

		if ($container->exists($key))
		{
			return $container->get($key);
		}

		return null;
	}

	/**
	 * getPackages
	 *
	 * @param Container $container
	 *
	 * @return  array
	 */
	public static function getPackages(Container $container = null)
	{
		$container = $container ? : Ioc::factory();

		$config = $container->get('system.config');

		$packages = $config->get('package');

		$return = array();

		foreach ((array) $packages as $pkg)
		{
			$return[$pkg->name] = static::getPackage($pkg->name);
		}

		return $return;
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
