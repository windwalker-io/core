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
use Windwalker\Console\Command\Command;
use Windwalker\Console\Console;
use Windwalker\Core\Application\Middleware\AbstractWebMiddleware;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Cache\CacheFactory;
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Controller\AbstractController;
use Windwalker\Core\Mvc\MvcResolver;
use Windwalker\Core\Package\Middleware\AbstractPackageMiddleware;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\Core\View\AbstractView;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\ListenerPriority;
use Windwalker\IO\Input;
use Windwalker\IO\PsrInput;
use Windwalker\Middleware\Chain\Psr7ChainBuilder;
use Windwalker\Middleware\Psr7Middleware;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The AbstractPackage class.
 *
 * @property-read  Structure                  $config
 * @property-read  PackageRouter              $router
 * @property-read  PsrInput                   $input
 * @property-read  WebApplication|CoreConsole $app
 * @property-read  string                     $name
 * @property-read  Container                  $container
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
	 * @var  AbstractController
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
	 * @var  Structure
	 */
	protected $config;

	/**
	 * Property middlewares.
	 *
	 * @var  PriorityQueue
	 */
	protected $middlewares;

	/**
	 * Property router.
	 *
	 * @var  PackageRouter
	 */
	protected $router;

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
	 * @return AbstractController
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

			$input = $input ? : $container->get('input');

			$controller = $resolver->create($task, $input, $this, $container);

			$container->share($key, $controller);
		}

		return $container->get($key);
	}

	/**
	 * execute
	 *
	 * @param string|AbstractController $controller
	 * @param Request                   $request
	 * @param Response                  $response
	 * @param bool                      $hmvc
	 *
	 * @return Response
	 */
	public function execute($controller, Request $request, Response $response, $hmvc = false)
	{
		if (!$controller instanceof AbstractController)
		{
			$controller = $this->getController($controller);
		}

		if ($hmvc)
		{
			$controller->isHmvc($hmvc);
		}

		$this->currentController = $controller;

		$chain = $this->getMiddlewareChain()->setEndMiddleware([$this, 'dispatch']);

		return $chain->execute($request, $response);
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

		$controller->setRequest($request)->setResponse($response);

		$this->prepareExecute();

		// @event: onPackageBeforeExecute
		$this->getDispatcher()->triggerEvent('onPackageBeforeExecute', array(
			'package'    => $this,
			'controller' => &$controller,
			'task'       => $controller,
		));

		$result = $controller->execute();

		$result = $this->postExecute($result);

		// @event: onPackageAfterExecute
		$this->getDispatcher()->triggerEvent('onPackageAfterExecute', array(
			'package'    => $this,
			'controller' => $controller,
			'result'     => &$result
		));

		$controller->redirect();

		$response = $controller->getResponse();

		if ($result !== null)
		{
			// Render view if return value is a view object,
			// don't use (string) keyword to make sure we can get Exception when error occurred.
			// @see  https://bugs.php.net/bug.php?id=53648
			if ($result instanceof AbstractView)
			{
				$result = $result->render();
			}

			$response->getBody()->write((string) $result);
		}

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

		$sysProvider = new PackageProvider($this);
		$sysProvider->boot();

		$container->registerServiceProvider($sysProvider);

		$providers = (array) $this->get('providers');

		foreach ($providers as $provider)
		{
			if (is_string($provider) && class_exists($provider))
			{
				$provider = new $provider($this);
			}

			if (!$provider)
			{
				continue;
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
		$listeners = (array) $this->get('listeners', array());

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
			elseif (class_exists($listener['class']))
			{
				$dispatcher->addListener(new $listener['class']($this), $listener['priority']);
			}
		}
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
		$commands = (array) $this->get('console.commands');

		foreach ($commands as $class)
		{
			if (class_exists($class) && is_subclass_of($class, Command::class))
			{
				$console->addCommand($class);
			}
		}
	}

	/**
	 * registerMiddlewares
	 */
	protected function registerMiddlewares()
	{
		// init middlewares
		$queue = $this->getMiddlewares();

		$middlewares = (array) $this->get('middlewares', []);

		$queue->insertArray($middlewares);

		// Remove closures
		$this->set('middlewares', null);
	}

	/**
	 * addMiddleware
	 *
	 * @param callable $middleware
	 * @param int      $priority
	 *
	 * @return static
	 */
	public function addMiddleware($middleware, $priority = PriorityQueue::NORMAL)
	{
		$this->getMiddlewares()->insert($middleware, $priority);

		return $this;
	}

	/**
	 * getMiddlewareChain
	 *
	 * @return  Psr7ChainBuilder
	 */
	public function getMiddlewareChain()
	{
		$middlewares = array_reverse(iterator_to_array(clone $this->getMiddlewares()));

		$chain = new Psr7ChainBuilder;

		foreach ($middlewares as $middleware)
		{
			if (is_string($middleware) && is_subclass_of($middleware, AbstractWebMiddleware::class))
			{
				$middleware = new Psr7Middleware(new $middleware($this->app, $this));
			}
			elseif ($middleware instanceof \Closure)
			{
				$middleware->bindTo($this);
			}

			$chain->add($middleware);
		}

		return $chain;
	}

	/**
	 * Method to get property Middlewares
	 *
	 * @return  PriorityQueue
	 */
	public function getMiddlewares()
	{
		if (!$this->middlewares)
		{
			$this->middlewares = new PriorityQueue;
		}

		return $this->middlewares;
	}

	/**
	 * Method to set property middlewares
	 *
	 * @param   PriorityQueue $middlewares
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMiddlewares(PriorityQueue $middlewares)
	{
		$this->middlewares = $middlewares;

		return $this;
	}

	/**
	 * loadConfiguration
	 *
	 * @param   Structure $config
	 *
	 * @return  static
	 */
	public function loadConfig(Structure $config)
	{
		$cache = CacheFactory::create('config', 'php_file', 'php_file', ['group' => 'config']);

		$cacheKey = 'config.package.' . $this->name;

		if ($this->app->getMode() != 'dev' && $this->app->getName() != 'dev' && $cache->exists($cacheKey))
		{
			$config->load($cache->get($cacheKey));

			return $this;
		}

		$file = $this->getDir() . '/Resources/config/config.dist.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php', ['load_raw' => true]);
		}

		// Override
		$file = $this->container->get('config')->get('path.etc') . '/package/' . $this->name . '.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php', ['load_raw' => true]);
		}

		$cache->set($cacheKey, $config->toArray());

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
		$routing = (array) $this->get('routing.files');

		$router->group($group, function (CoreRouter $router) use ($routing)
		{
			$router->addRouteFromFiles($routing, $this);

			if (is_file($this->getDir() . '/routing.yml'))
			{
				$router->addRouteFromFile($this->getDir() . '/routing.yml', $this);
			}
		});

		return $router;
	}

	/**
	 * getRouter
	 *
	 * @return  PackageRouter
	 */
	public function getRouter()
	{
		if (!$this->router)
		{
			$this->router = new PackageRouter($this->getContainer()->get('router'), $this);
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
	public function setRouter($router)
	{
		$this->router = $router;

		return $this;
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
	 * getFile
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
		return $this->getContainer()->get('dispatcher');
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
		$this->getContainer()->set('dispatcher', $dispatcher);

		return $this;
	}

	/**
	 * Method to get property Config
	 *
	 * @return  Structure
	 *
	 * @since   2.1
	 */
	public function getConfig()
	{
		if (!$this->config)
		{
			$this->config = new Structure;

			$this->loadConfig($this->config);
		}

		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Structure $config
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
	 * @return  AbstractController
	 */
	public function getCurrentController()
	{
		return $this->currentController;
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
			'app'        => 'application',
			'input'      => 'input',
			'dispatcher' => 'dispatcher'
		];

		if (isset($diMapping[$name]))
		{
			return $this->getContainer()->get($diMapping[$name]);
		}

		if ($name == 'container')
		{
			return $this->getContainer();
		}

		if ($name == 'config')
		{
			return $this->getConfig();
		}

		if ($name == 'router')
		{
			return $this->getRouter();
		}

		if ($name == 'name')
		{
			return $this->getName();
		}

		return null;
	}
}
