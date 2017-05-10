<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\Structure\Structure;
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
	protected $packages = [];

	/**
	 * Property aliases.
	 *
	 * @var  array
	 */
	protected $aliases = [];

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

		if (is_string($package))
		{
			if (!class_exists($package))
			{
				return null;
			}

			/** @var \Windwalker\Core\Package\AbstractPackage $package */
			$package = $this->container->newInstance($package);
		}

		if (!$package)
		{
			return null;
		}

		// If we set custom name to package, use this as alias.
		if (!is_numeric($alias))
		{
			$package->setName($alias);
		}

		// Get package identify name.
		$name = $package->getName();

		// Set container and init it
		$subContainer = $container->createChild($name);

		$package->setContainer($subContainer)->boot();

		/** @var WindwalkerApplicationInterface $application */
		$application = $container->get('application');

		// If in Console mode, register commands.
		if ($application->isConsole())
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
	 * setCurrentPackage
	 *
	 * @param AbstractPackage $package
	 *
	 * @return  static
	 */
	public function setCurrentPackage(AbstractPackage $package)
	{
		$this->container->share('current.package', $package);

		return $this;
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

		$package = new DefaultPackage;

		$package->setContainer($this->container->createChild('_default'));

		$package->boot();

		return $package;
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
	 * @return  Structure
	 */
	public function getConfig($package)
	{
		return $this->getPackage($package)->getConfig();
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
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
