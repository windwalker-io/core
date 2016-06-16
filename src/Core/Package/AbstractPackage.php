<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Yaml\Yaml;
use Windwalker\Console\Console;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Console\WindwalkerConsole;
use Windwalker\Core\Controller\Controller;
use Windwalker\Core\Mvc\MvcResolver;
use Windwalker\Core\Package\Middleware\AbstractPackageMiddleware;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\ListenerPriority;
use Windwalker\Filesystem\Path\PathLocator;
use Windwalker\IO\Input;
use Windwalker\IO\PsrInput;
use Windwalker\Middleware\Chain\Psr7ChainBuilder;
use Windwalker\Registry\Registry;

/**
 * The AbstractPackage class.
 *
 * @property-read  Registry                          $config
 * @property-read  PackageRouter                     $router
 * @property-read  PsrInput                          $input
 * @property-read  WebApplication|WindwalkerConsole  $app
 * @property-read  string                            $name
 *
 * @since  2.0
 */
class AbstractPackage implements DispatcherAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Bundle name.
	 *
	 * @var  string
	 */
	protected $name;

	/**
	 * Property enabled.
	 *
	 * @var  boolean
	 */
	protected $isEnabled = true;

	/**
	 * Property currentController.
	 *
	 * @var  Controller
	 */
	protected $currentController;

	/**
	 * Property task.
	 *
	 * @var  string
	 */
	protected $task;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config;

	/**
	 * Property middlewares.
	 *
	 * @var  Psr7ChainBuilder
	 */
	protected $middlewares;

	/**
	 * initialise
	 *
	 * @throws  \LogicException
	 * @return  void
	 */
	public function boot()
	{
		if (!$this->name)
		{
			throw new \LogicException('Package: ' . get_class($this) . ' name property should not be empty.');
		}

		$this->getConfig();

		$this->registerProviders($this->getContainer());

		$this->registerListeners($this->getDispatcher());

		$this->registerMiddlewares();
	}

	/**
	 * getController
	 *
	 * @param string       $task
	 * @param array|Input  $input
	 * @param bool         $forceNew
	 *
	 * @return Controller
	 */
	public function getController($task, $input = null, $forceNew = false)
	{
		$resolver = $this->getMvcResolver()->getControllerResolver();

		$key = $resolver::getDIKey($task);

		$container = $this->getContainer();

		if (!$container->exists($key) || $forceNew)
		{
			if ($input !== null && !$input instanceof Input)
			{
				$input = new Input($input);
			}

			$input = $input ? : $container->get('system.input');

			$controller = $resolver->create($task, $input, $container->get('system.application'), $container, $this);

			$container->share($key, $controller);
		}

		return $container->get($key);
	}

	/**
	 * execute
	 *
	 * @param string|Controller $controller
	 * @param Request           $request
	 * @param Response          $response
	 *
	 * @return  Response
	 */
	public function execute($controller, Request $request, Response $response)
	{
		if (!$controller instanceof Controller)
		{
			$controller = $this->getController($controller);
		}

		$controller->setRequest($request)->setResponse($response);

		// TODO: rewrite hmvc
//		if ($controller)
//		{
//			$controller->isHmvc($hmvc);
//		}

		$this->currentController = $controller;

		return $this->middlewares->execute($request, $response);
	}

	/**
	 * dispatch
	 *
	 * @param Request  $request
	 * @param Response $response
	 * @param callable $next
	 *
	 * @return Response
	 */
	public function dispatch(Request $request, Response $response, $next = null)
	{
		$controller = $this->currentController;

		$this->prepareExecute();

		$this->getDispatcher()->triggerEvent('onPackageBeforeExecute', array(
			'package'    => $this,
			'controller' => &$controller,
			'task'       => $controller,
			//			'variables'  => $variables,
			//			'hmvc'       => $hmvc
		));

		$result = $controller->execute();

		$result = $this->postExecute($result);

		$this->getDispatcher()->triggerEvent('onPackageAfterExecute', array(
			'package'    => $this,
			'controller' => $controller,
			//			'task'       => $task,
			//			'variables'  => $variables,
			//			'hmvc'       => $hmvc,
			'result'     => &$result
		));

		$controller->redirect();

		$response->getBody()->write($result);

		return $response;
	}

	/**
	 * prepareExecute
	 *
	 * @return  void
	 */
	protected function prepareExecute()
	{
	}

	/**
	 * postExecute
	 *
	 * @param   mixed  $result
	 *
	 * @return  mixed
	 */
	protected function postExecute($result = null)
	{
		return $result;
	}

	/**
	 * getMvcResolver
	 *
	 * @return  MvcResolver
	 */
	public function getMvcResolver()
	{
		return $this->container->get('mvc.resolver');
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
		return $this->config->get($name, $default);
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
		$this->config->set($name, $value);

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
		/** @var Container $container */
		$container = $this->getContainer();

		$container->registerServiceProvider(new PackageProvider($this));

		$providers = (array) $this->get('providers');

		foreach ($providers as $provider)
		{
			if (is_string($provider) && class_exists($provider))
			{
				$provider = new $provider;
			}

			if (is_callable($provider, 'boot'))
			{
				$provider->boot($container);
			}

			$container->registerServiceProvider($provider);
		}
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

			if (is_callable($listener['class']) && !is_numeric($name))
			{
				$dispatcher->listen($name, $listener['class']);
			}
			else
			{
				$dispatcher->addListener(new $listener['class'], $listener['priority']);
			}
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

		if (is_file($file))
		{
			$config->loadFile($file, 'yaml');
		}

		// Override
		$file = $this->container->get('system.config')->get('path.etc') . '/package/' . $this->name . '.yml';

		if (is_file($file))
		{
			$config->loadFile($file, 'yaml');
		}

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

		return (array) Yaml::parse(file_get_contents($file));
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
	 * registerMiddlewares
	 */
	protected function registerMiddlewares()
	{
		// init middlewares
		$this->getMiddlewares();

		$middlewares = (array) $this->get('middlewares', []);

		krsort($middlewares);

		foreach ($middlewares as $middleware)
		{
			$this->addMiddleware($middleware);
		}

		// Remove closures
		$this->set('middlewares', null);
	}

	/**
	 * addMiddleware
	 *
	 * @param callable $middleware
	 *
	 * @return  static
	 */
	public function addMiddleware($middleware)
	{
		if (is_string($middleware) && is_subclass_of($middleware, AbstractPackageMiddleware::class))
		{
			$middleware = new $middleware($this);
		}
		elseif ($middleware instanceof \Closure)
		{
			$middleware->bindTo($this);
		}

		$this->getMiddlewares()->add($middleware);

		return $this;
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

	/**
	 * Method to get property CurrentController
	 *
	 * @return  Controller
	 */
	public function getCurrentController()
	{
		return $this->currentController;
	}

	/**
	 * Method to get property Middlewares
	 *
	 * @return  Psr7ChainBuilder
	 */
	public function getMiddlewares()
	{
		if (!$this->middlewares)
		{
			$this->middlewares = new Psr7ChainBuilder;

			$this->middlewares->add([$this, 'dispatch']);
		}

		return $this->middlewares;
	}

	/**
	 * Method to set property middlewares
	 *
	 * @param   Psr7ChainBuilder $middlewares
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMiddlewares($middlewares)
	{
		$this->middlewares = $middlewares;

		return $this;
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
		$diMapping = [
			'app'        => 'system.application',
			'input'      => 'system.input',
			'dispatcher' => 'system.dispatcher',
			'router'     => 'system.router'
		];

		if (isset($diMapping[$name]))
		{
			return $this->container->get($diMapping[$name]);
		}

		if ($name == 'config')
		{
			return $this->getConfig();
		}

		if ($name == 'name')
		{
			return $this->getName();
		}

		return null;
	}
}
