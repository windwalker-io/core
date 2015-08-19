<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Symfony\Component\Yaml\Yaml;
use Windwalker\Console\Console;
use Windwalker\Core\Controller\Controller;
use Windwalker\Core\Controller\ControllerResolver;
use Windwalker\Core\Controller\MultiActionController;
use Windwalker\Core\Ioc;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\DI\Container;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\ListenerPriority;
use Windwalker\Filesystem\Path\PathLocator;
use Windwalker\IO\Input;
use Windwalker\Registry\Registry;
use Windwalker\Registry\RegistryHelper;
use Windwalker\String\StringHelper;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The AbstractPackage class.
 *
 * @property-read  PackageRouter  $router
 * 
 * @since  2.0
 */
class AbstractPackage implements DispatcherAwareInterface
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
	 * Property enabled.
	 *
	 * @var  boolean
	 */
	protected $isEnabled = true;

	/**
	 * Property router.
	 *
	 * @var PackageRouter
	 */
	protected $router;

	/**
	 * Property task.
	 *
	 * @var  string
	 */
	protected $task;

	/**
	 * Property config.
	 *
	 * @var  array
	 */
	protected $variables;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config;

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

		$this->registerProviders($container);

		$this->registerListeners($container->get('system.dispatcher'));
	}

	/**
	 * getController
	 *
	 * @param string  $task
	 * @param array   $variables
	 *
	 * @return  Controller
	 */
	public function getController($task = null, $variables = array())
	{
		if ($variables instanceof Input)
		{
			$variables = $variables->getArray();
		}

		$controller = $task ? : $this->getTask();

		list($controller, $action) = StringHelper::explode('::', $controller, 2);

		$class = ControllerResolver::getController($this, $controller);

		$container = $this->getContainer();

		$controller = new $class($container->get('system.input'), $container->get('system.application'), $container, $this);

		if (!($controller instanceof Controller))
		{
			throw new \UnexpectedValueException(
				sprintf('Controller: %s should be sub class of \Windwalker\Core\Controller\Controller', $controller)
			);
		}

		if ($controller instanceof MultiActionController)
		{
			$controller->setActionName($action);
			$controller->setArguments($variables ? : $this->variables);
		}

		return $controller;
	}

	/**
	 * execute
	 *
	 * @param string $task
	 * @param array  $variables
	 * @param bool   $hmvc
	 *
	 * @return mixed
	 */
	public function execute($task = null, $variables = array(), $hmvc = false)
	{
		$controller = $this->getController($task, $variables);

		$this->getDispatcher()->triggerEvent('onPackageBeforeExecute', array(
			'package'    => $this,
			'controller' => &$controller,
			'task'       => $task,
			'variables'  => $variables,
			'hmvc'       => $hmvc
		));

		$result = $controller->execute();

		$this->getDispatcher()->triggerEvent('onPackageAfterExecute', array(
			'package'    => $this,
			'controller' => $controller,
			'task'       => $task,
			'variables'  => $variables,
			'hmvc'       => $hmvc,
			'result'     => &$result
		));

		$controller->redirect();

		return $result;
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
			$this->container = Ioc::factory($this->getName());
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
	 * Method to get property Router
	 *
	 * @return  PackageRouter
	 */
	public function getRouter()
	{
		if (!$this->router)
		{
			$this->router = new PackageRouter($this, $this->getContainer()->get('system.router'));
		}

		return $this->router;
	}

	/**
	 * Method to set property router
	 *
	 * @param   PackageRouter $router
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRouter(PackageRouter $router)
	{
		$this->router = $router;

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
	 * @param   Registry  $config
	 *
	 * @return  static
	 */
	public function loadConfig(Registry $config)
	{
		$file = $this->getDir() . '/config.yml';

		if (!is_file($file))
		{
			return $this;
		}

		$config->loadFile($file, 'yaml');

		return $this;
	}

	/**
	 * loadRouting
	 *
	 * @return  mixed
	 */
	public function loadRouting()
	{
		$file = $this->getDir() . '/routing.yml';

		if (!is_file($file))
		{
			return null;
		}

		return Yaml::parse(file_get_contents($file));
	}

	/**
	 * getRoot
	 *
	 * @note Reflection does not need to cache.
	 * @see https://gist.github.com/mindplay-dk/3359812
	 *
	 * @return  string
	 */
	public function getFile()
	{
		$ref = new \ReflectionClass(get_called_class());

		return $ref->getFileName();
	}

	/**
	 * getDir
	 *
	 * @return  string
	 */
	public function getDir()
	{
		return dirname($this->getFile());
	}

	/**
	 * enable
	 *
	 * @return  static
	 */
	public function enable()
	{
		$this->isEnabled = true;

		return $this;
	}

	/**
	 * disable
	 *
	 * @return  static
	 */
	public function disable()
	{
		$this->isEnabled = false;

		return $this;
	}

	/**
	 * isEnabled
	 *
	 * @return  bool
	 */
	public function isEnabled()
	{
		return $this->isEnabled;
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

	/**
	 * __get
	 *
	 * @param string $name
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ($name == 'router')
		{
			return $this->getRouter();
		}

		if ($name == 'config')
		{
			return $this->getConfig();
		}

		return null;
	}

	/**
	 * Method to get property Task
	 *
	 * @return  string
	 *
	 * @since   2.1
	 */
	public function getTask()
	{
		return $this->task;
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
		$this->task = $task;

		return $this;
	}

	/**
	 * Method to get property Variables
	 *
	 * @return  array
	 *
	 * @since   2.1
	 */
	public function getVariables()
	{
		return $this->variables;
	}

	/**
	 * Method to set property variables
	 *
	 * @param   array $variables
	 *
	 * @return  static  Return self to support chaining.
	 *
	 * @since   2.1
	 */
	public function setVariables($variables)
	{
		$this->variables = $variables;

		return $this;
	}

	/**
	 * getDispatcher
	 *
	 * @return  DispatcherInterface
	 */
	public function getDispatcher()
	{
		return $this->getContainer()->get('system.dispatcher');
	}

	/**
	 * setDispatcher
	 *
	 * @param   DispatcherInterface $dispatcher
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->getContainer()->set('system.dispatcher', $dispatcher);

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
		$this->config = $config;

		return $this;
	}
}
