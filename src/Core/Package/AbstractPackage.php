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
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Controller\AbstractController;
use Windwalker\Core\Mvc\MvcResolver;
use Windwalker\Core\Router\MainRouter;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Security\CsrfGuard;
use Windwalker\Core\View\AbstractView;
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
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The AbstractPackage class.
 *
 * @property-read  Structure                  $config
 * @property-read  PackageRouter              $router
 * @property-read  PsrInput                   $input
 * @property-read  WebApplication|CoreConsole $app
 * @property-read  string                     $name
 * @property-read  Container                  $container
 * @property-read  CsrfGuard                  $csrf
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
	 * Property booted.
	 *
	 * @var  boolean
	 */
	protected $booted = false;

	/**
	 * initialise
	 *
	 * @throws  \LogicException
	 * @return  void
	 */
	public function boot()
	{
		if ($this->booted)
		{
			return;
		}

		if (!$this->name)
		{
			throw new \LogicException('Package: ' . get_class($this) . ' name property should not be empty.');
		}

		$this->getConfig();

		$this->registerProviders($this->getContainer());

		$this->registerListeners($this->getDispatcher());

		$this->registerMiddlewares();

		$this->booted = true;
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
		$this->getDispatcher()->triggerEvent('onPackageBeforeExecute', [
			'package'    => $this,
			'controller' => &$controller,
			'task'       => $controller,
		]
		);

		$result = $controller->execute();

		$result = $this->postExecute($result);

		// @event: onPackageAfterExecute
		$this->getDispatcher()->triggerEvent('onPackageAfterExecute', [
			'package'    => $this,
			'controller' => $controller,
			'result'     => &$result
		]
		);

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
			elseif (is_array($result))
			{
				$result = json_encode($result);
			}

			$response->getBody()->write((string) $result);
		}

		return $response;
	}

	/**
	 * run
	 *
	 * @param string|AbstractController  $task
	 * @param array|Input                $input
	 *
	 * @return  Response
	 */
	public function executeTask($task, $input = null)
	{
		return $this->execute($this->getController($task, $input), $this->app->request, new \Windwalker\Http\Response\Response);
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
		$sysProvider = new PackageProvider($this);
		$container->registerServiceProvider($sysProvider);

		$sysProvider->boot();

		$providers = (array) $this->get('providers');

		foreach ($providers as &$provider)
		{
			if (is_string($provider) && class_exists($provider))
			{
				$provider = $container->newInstance($provider);
			}

			if (!$provider)
			{
				continue;
			}

			$container->registerServiceProvider($provider);

			if (is_callable($provider, 'boot'))
			{
				$provider->boot($container);
			}
		}

		foreach ($providers as $provider)
		{
			if (is_callable([$provider, 'bootDeferred']))
			{
				$provider->bootDeferred($container);
			}
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
		$listeners = (array) $this->get('listeners', []);

		$defaultOptions = [
			'class'    => '',
			'priority' => ListenerPriority::NORMAL,
			'enabled'  => true
		];

		foreach ($listeners as $name => $listener)
		{
			if (is_string($listener))
			{
				$listener = ['class' => $listener];
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
				$dispatcher->addListener($this->container->newInstance($listener['class']), $listener['priority']);
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
				$middleware = new Psr7Middleware($this->container->newInstance($middleware));
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
		$file = $this->getDir() . '/Resources/config/config.dist.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php', ['load_raw' => true]);
		}

		// Override
		$file = $this->getContainer()->get('config')->get('path.etc') . '/package/' . $this->name . '.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php', ['load_raw' => true]);
		}

		return $this;
	}

	/**
	 * loadRouting
	 *
	 * @param MainRouter $router
	 * @param string     $group
	 *
	 * @return MainRouter
	 */
	public function loadRouting(MainRouter $router, $group = null)
	{
		$routing = (array) $this->get('routing.files');

		$router->group($group, function (MainRouter $router) use ($routing)
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
	 * getMvcResolver
	 *
	 * @return  MvcResolver
	 */
	public function getMvcResolver()
	{
		return $this->getContainer()->get('mvc.resolver');
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
	 * getNamespace
	 *
	 * @return  string
	 *
	 * @since  3.1
	 */
	public function getNamespace()
	{
		return ReflectionHelper::getNamespaceName($this);
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
			'dispatcher' => 'dispatcher',
			'csrf'       => 'security.csrf',
			'router'     => 'router'
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

		if ($name == 'name')
		{
			return $this->getName();
		}

		return null;
	}
}
