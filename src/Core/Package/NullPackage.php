<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\Core\Ioc;
use Windwalker\Core\Object\SilencerInterface;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\DI\Container;
use Windwalker\Event\Dispatcher;

/**
 * The NullPackage class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class NullPackage extends AbstractPackage implements SilencerInterface
{
	/**
	 * Property dir.
	 *
	 * @var string
	 */
	public $dir;

	/**
	 * __get
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ($name == 'router')
		{
			if (!$this->router)
			{
				$this->router = new PackageRouter($this, Ioc::getRouter());
			}

			return $this->router;
		}

		return null;
	}

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
	public function initialise()
	{
	}

	/**
	 * buildRoute
	 *
	 * @param string         $route
	 * @param boolean|string $package
	 *
	 * @return  string
	 */
	public function buildRoute($route, $package = null)
	{
		return null;
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
	 * Method to get property RoutingPrefix
	 *
	 * @return  string
	 */
	public function getRoutingPrefix()
	{
		return '';
	}

	/**
	 * Method to set property routingPrefix
	 *
	 * @param   string $routingPrefix
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRoutingPrefix($routingPrefix)
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
	 * @param Dispatcher $dispatcher
	 *
	 * @return  void
	 */
	public function registerListeners(Dispatcher $dispatcher)
	{
	}

	/**
	 * loadConfiguration
	 *
	 * @throws  \RuntimeException
	 * @return  array
	 */
	public function loadConfig()
	{
		return array();
	}

	/**
	 * loadRouting
	 *
	 * @return  mixed
	 */
	public function loadRouting()
	{
		return array();
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
	public static function registerCommands(Console $console)
	{
	}
}
