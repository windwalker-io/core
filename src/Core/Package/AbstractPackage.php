<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Symfony\Component\Yaml\Yaml;
use Windwalker\Console\Console;
use Windwalker\Core\Ioc;
use Windwalker\Core\Router\Router;
use Windwalker\DI\Container;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\ListenerPriority;
use Windwalker\Filesystem\Path\PathLocator;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The AbstractPackage class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class AbstractPackage
{
	/**
	 * DI Container.
	 *
	 * @var Container
	 */
	protected $container = null;

	/**
	 * Bundle name.
	 *
	 * @var  string
	 */
	protected $name = null;

	/**
	 * Property routingPrefix.
	 *
	 * @var  string
	 */
	protected $routingPrefix = null;

	/**
	 * initialise
	 *
	 * @throws  \LogicException
	 * @return  void
	 */
	public function initialise()
	{
		if (!$this->name)
		{
			throw new \LogicException('Package: ' . get_class($this) . ' name property should not be empty.');
		}

		$container = $this->getContainer();

		$container->registerServiceProvider(new PackageProvider($this->getName(), $this));

		$this->registerProviders($container);

		$this->registerListeners($container->get('system.dispatcher'));
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
		if ($package === false)
		{
			// Nothing
		}
		elseif (!$package)
		{
			$route = $this->getRoutingPrefix() . ':' . $route;
		}
		else
		{
			$route = $package . ':' . $route;
		}

		return Router::build($route);
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @since   1.0
	 *
	 * @throws  \UnexpectedValueException May be thrown if the container has not been set.
	 */
	public function getContainer()
	{
		if (!$this->container)
		{
			$this->container = Ioc::getContainer($this->getName());
		}

		return $this->container;
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
		$this->container = $container;

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
		return $this->container->get('system.config')->get('package.' . $this->getName() . '.config.' . $name, $default);
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
		$this->container->get('system.config')->set('package.' . $this->getName() . '.config.' . $name, $value);

		return $this;
	}

	/**
	 * Method to get property RoutingPrefix
	 *
	 * @return  string
	 */
	public function getRoutingPrefix()
	{
		return $this->routingPrefix ? : $this->getName();
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
		$this->routingPrefix = $routingPrefix;

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
		$listeners = $this->get('listener', array());

		$defaultOptions = array(
			'class'    => '',
			'priority' => ListenerPriority::NORMAL,
			'enabled'  => true
		);

		foreach ($listeners as $name => $listener)
		{
			if (is_string($listener))
			{
				$listener = array('class' => $listener);
			}

			$listener = array_merge($defaultOptions, (array) $listener);

			if (!$listener['enabled'])
			{
				continue;
			}

			$dispatcher->addListener(new $listener['class'], $listener['priority']);
		}
	}

	/**
	 * loadConfiguration
	 *
	 * @throws  \RuntimeException
	 * @return  array
	 */
	public static function loadConfig()
	{
		$file = static::getDir() . '/config.yml';

		if (!is_file($file))
		{
			return null;
		}

		return Yaml::parse(file_get_contents($file));
	}

	/**
	 * loadRouting
	 *
	 * @return  mixed
	 */
	public static function loadRouting()
	{
		$file = static::getDir() . '/routing.yml';

		if (!is_file($file))
		{
			return null;
		}

		return Yaml::parse(file_get_contents($file));
	}

	/**
	 * getRoot
	 *
	 * @return  string
	 */
	public static function getFile()
	{
		return ReflectionHelper::getPath(get_called_class());
	}

	/**
	 * getDir
	 *
	 * @return  string
	 */
	public static function getDir()
	{
		return dirname(static::getFile());
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
		$reflection = new \ReflectionClass(get_called_class());

		$namespace = $reflection->getNamespaceName();

		$path = dirname($reflection->getFileName()) . '/Command';

		if (!is_dir($path))
		{
			return;
		}

		$path = new PathLocator($path);

		foreach ($path as $file)
		{
			/** @var \SplFileInfo $file */
			if (!$file->isFile())
			{
				continue;
			}

			$class = $namespace . '\\Command\\' . $file->getBasename('.php');

			$enabled = property_exists($class, 'isEnabled') ? $class::$isEnabled : true;

			if (class_exists($class) && is_subclass_of($class, 'Windwalker\\Console\\Command\\Command') && $enabled)
			{
				$console->addCommand(new $class);
			}
		}
	}
}
