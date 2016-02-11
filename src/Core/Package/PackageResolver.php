<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\Registry\Registry;
use Windwalker\String\StringNormalise;

/**
 * The PackageResolver class.
 *
 * @since  2.1.1
 */
class PackageResolver implements ContainerAwareInterface
{
	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * Property packages.
	 *
	 * @var  AbstractPackage[]
	 */
	protected $packages = array();

	/**
	 * Property aliases.
	 *
	 * @var  array
	 */
	protected $aliases = array();

	/**
	 * PackageResolver constructor.
	 *
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * registerPackages
	 *
	 * @param   array|AbstractPackage[] $packages
	 *
	 * @return  static
	 */
	public function registerPackages(array $packages)
	{
		foreach ($packages as $alias => $package)
		{
			$this->addPackage($alias, $package);
		}

		return $this;
	}

	/**
	 * addPackage
	 *
	 * @param string                  $alias
	 * @param string|AbstractPackage  $package
	 *
	 * @return  AbstractPackage
	 */
	public function addPackage($alias, $package)
	{
		$container = $this->getContainer();
		$config = $container->get('system.config');

		if (is_string($package))
		{
			if (!class_exists($package))
			{
				return null;
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

		// Override package config from etc
		$file = $config->get('path.etc') . '/packages/' . $name . '.yml';

		if (is_file($file))
		{
			$package->getConfig()->loadFile($file);
		}

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
		$this->packages[$name] = $package;

		$subContainer->alias('package', 'package.' . $name);

		// Add alias map
		$this->aliases[get_class($package)] = $name;

		return $package;
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage($name = null)
	{
		if (!$name)
		{
			return $this->getCurrentPackage();
		}

		if (isset($this->packages[$name]))
		{
			return $this->packages[$name];
		}

		return null;
	}

	/**
	 * getCurrentPackage
	 *
	 * @return  AbstractPackage
	 */
	public function getCurrentPackage()
	{
		if (!$this->container->exists('current.package'))
		{
			return null;
		}

		return $this->container->get('current.package');
	}

	/**
	 * getPackageAlias
	 *
	 * @param   string|AbstractPackage $package
	 *
	 * @return  string
	 */
	public function getAlias($package)
	{
		if (is_string($package))
		{
			$package = ltrim(StringNormalise::toClassNamespace($package), '\\');
		}
		elseif (is_object($package))
		{
			$package = get_class($package);
		}
		else
		{
			throw new \InvalidArgumentException('Please send package object or class name.');
		}

		if (isset($this->aliases[$package]))
		{
			return $this->aliases[$package];
		}

		return null;
	}

	/**
	 * resolvePackage
	 *
	 * @param  string  $name
	 *
	 * @return  AbstractPackage
	 */
	public function resolvePackage($name)
	{
		if ($this->exists($name))
		{
			return $this->getPackage($name);
		}

		return new DefaultPackage;
	}

	/**
	 * removePackage
	 *
	 * @param   string  $name
	 *
	 * @return  static
	 */
	public function removePackage($name)
	{
		if ($this->exists($name))
		{
			$package = $this->getPackage($name);

			unset($this->aliases[get_class($package)]);

			unset($this->packages[$name]);

			$this->container->removeChild($name);

			$this->container->remove('package.' . $name);
		}

		return $this;
	}

	/**
	 * getPackages
	 *
	 * @return  AbstractPackage[]
	 */
	public function getPackages()
	{
		return $this->packages;
	}

	/**
	 * exists
	 *
	 * @param   string  $package
	 *
	 * @return  boolean
	 */
	public function exists($package)
	{
		return array_key_exists($package, $this->packages);
	}

	/**
	 * getConfig
	 *
	 * @param  string  $package
	 *
	 * @return  Registry
	 */
	public function getConfig($package)
	{
		return $this->getPackage($package)->getConfig();
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @throws  \UnexpectedValueException May be thrown if the container has not been set.
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  static
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}
}
