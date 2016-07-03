<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\Core\Ioc;
use Windwalker\Core\Object\SilencerObjectInterface;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\DI\Container;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Registry\Registry;

/**
 * The NullPackage class.
 * 
 * @since  2.0
 */
class NullPackage extends AbstractPackage implements SilencerObjectInterface
{
	/**
	 * Property dir.
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * __set
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		return;
	}

	/**
	 * __isset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __isset($name)
	{
		return false;
	}

	/**
	 * __toString
	 *
	 * @return  mixed
	 */
	public function __toString()
	{
		return null;
	}

	/**
	 * __unset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __unset($name)
	{
		return;
	}

	/**
	 * __call
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return  mixed
	 */
	public function __call($name, $args)
	{
		return null;
	}

	/**
	 * initialise
	 *
	 * @throws  \LogicException
	 * @return  void
	 */
	public function boot()
	{
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @since   2.0
	 */
	public function getContainer()
	{
		return Ioc::factory();
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  static Return self to support chaining.
	 *
	 * @since   1.0
	 */
	public function setContainer(Container $container)
	{
		return $this;
	}

	/**
	 * Get bundle name.
	 *
	 * @return  string  Bundle ame.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Method to set property name
	 *
	 * @param   string $name
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * get
	 *
	 * @param string $name
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function get($name, $default = null)
	{
		return null;
	}

	/**
	 * set
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return  static
	 */
	public function set($name, $value)
	{
		return $this;
	}

	/**
	 * Register providers.
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public function registerProviders(Container $container)
	{
	}

	/**
	 * registerListeners
	 *
	 * @param DispatcherInterface $dispatcher
	 *
	 * @return  void
	 */
	public function registerListeners(DispatcherInterface $dispatcher)
	{
	}

	/**
	 * loadConfiguration
	 *
	 * @param Registry $config
	 *
	 * @return static
	 */
	public function loadConfig(Registry $config)
	{
		return $this;
	}

	/**
	 * loadRouting
	 *
	 * @param CoreRouter $router
	 * @param string     $group
	 *
	 * @return CoreRouter
	 */
	public function loadRouting(CoreRouter $router, $group = null)
	{
		return $router;
	}

	/**
	 * getRoot
	 *
	 * @return  string
	 */
	public function getFile()
	{
		return null;
	}

	/**
	 * getDir
	 *
	 * @return  string
	 */
	public function getDir()
	{
		return $this->dir;
	}

	/**
	 * Register commands to console.
	 *
	 * @param Console $console Windwalker console object.
	 *
	 * @return  void
	 */
	public function registerCommands(Console $console)
	{
	}

	/**
	 * Method to set property task
	 *
	 * @param   string $task
	 *
	 * @return  static  Return self to support chaining.
	 *
	 * @since   2.1
	 */
	public function setTask($task)
	{
		return $this;
	}

	/**
	 * Method to get property Config
	 *
	 * @return  Registry
	 *
	 * @since   2.1
	 */
	public function getConfig()
	{
		if (!$this->config)
		{
			$this->config = new Registry;

			$this->loadConfig($this->config);
		}

		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Registry $config
	 *
	 * @return  static  Return self to support chaining.
	 *
	 * @since   2.1
	 */
	public function setConfig($config)
	{
		return $this;
	}
}
