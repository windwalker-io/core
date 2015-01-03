<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Application;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Application\Web\Response;
use Windwalker\Application\Web\ResponseInterface;
use Windwalker\Core\Controller\Controller;
use Windwalker\Core\Controller\MultiActionController;
use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Provider\CacheProvider;
use Windwalker\Core\Provider\DateTimeProvider;
use Windwalker\Core\Provider\EventProvider;
use Windwalker\Core\Provider\RouterProvider;
use Windwalker\Core\Provider\SessionProvider;
use Windwalker\Core\Provider\SystemProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\Router\Router;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Web\WebEnvironment;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\IO\Input;
use Windwalker\Registry\Registry;
use Windwalker\Router\Route;
use Windwalker\Utilities\ArrayHelper;

/**
 * The WebApplication class.
 * 
 * @since  2.0
 */
class WebApplication extends AbstractWebApplication implements WindwalkerApplicationInterface, DispatcherAwareInterface
{
	/**
	 * Property env.
	 *
	 * @var  string
	 */
	protected $mode = 'prod';

	/**
	 * Property router.
	 *
	 * @var  \Windwalker\Router\Router
	 */
	protected $router = null;

	/**
	 * Property container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The application configuration object.
	 *
	 * @var    Registry
	 * @since  2.0
	 */
	public $config;

	/**
	 * Class constructor.
	 *
	 * @param   Container          $container    The DI container object.
	 * @param   Input              $input        An optional argument to provide dependency injection for the application's
	 *                                           input object.
	 * @param   Registry           $config       An optional argument to provide dependency injection for the application's
	 *                                           config object.
	 * @param   WebEnvironment     $environment  An optional argument to provide dependency injection for the application's
	 *                                           environment object.
	 * @param   ResponseInterface  $response     The response object.
	 */
	public function __construct(Container $container = null, Input $input = null, Registry $config = null,
		WebEnvironment $environment = null, ResponseInterface $response = null)
	{
		$this->environment = $environment instanceof WebEnvironment    ? $environment : new WebEnvironment;
		$this->response    = $response    instanceof ResponseInterface ? $response    : new Response;
		$this->input       = $input       instanceof Input             ? $input       : new Input;
		$this->config      = $config      instanceof Registry          ? $config      : new Registry;
		$this->container   = $container   instanceof Container         ? $container   : Ioc::factory();

		$this->initialise();

		// Set the execution datetime and timestamp;
		$this->set('execution.datetime', gmdate('Y-m-d H:i:s'));
		$this->set('execution.timestamp', time());
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		$this->prepareSystemPath($this->config);

		$this->loadConfiguration($this->config);

		// Set System Providers
		$this->container->registerServiceProvider(new SystemProvider($this));
		$this->container->registerServiceProvider(new WebProvider($this));

		if ($this->config->get('system.debug'))
		{
			ErrorHandler::register();
		}

		$this->registerProviders($this->container);

		PackageHelper::registerPackages($this->loadPackages(), $this->container);

		$this->triggerEvent('onAfterInitialise', array('app' => $this));
	}

	/**
	 * registerProviders
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	protected function registerProviders(Container $container)
	{
		$providers = $this->loadProviders();

		foreach ($providers as $provider)
		{
			$container->registerServiceProvider($provider);
		}
	}

	/**
	 * loadProviders
	 *
	 * @return  ServiceProviderInterface[]
	 */
	public function loadProviders()
	{
		return array(
			'event'   => new EventProvider,
			'router'  => new RouterProvider,
			'cache'   => new CacheProvider,
			'session' => new SessionProvider,
			'datetime' => new DateTimeProvider,
		);
	}

	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public function loadPackages()
	{
		return array();
	}

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		$this->prepareExecute();

		$this->triggerEvent('onBeforeExecute', array('app' => $this));

		// Perform application routines.
		$this->doExecute();

		$this->triggerEvent('onAfterExecute', array('app' => $this));

		$this->postExecute();

		$this->triggerEvent('onBeforeRespond', array('app' => $this));

		// Send the application response.
		$this->respond();

		$this->triggerEvent('onAfterRespond', array('app' => $this));
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function doExecute()
	{
		$this->triggerEvent('onBeforeRouting', array('app' => $this));

		$this->route();

		$this->triggerEvent('onAfterRouting', array('app' => $this));

		$this->triggerEvent('onBeforeRender', array('app' => $this));

		$this->setBody($this->render());

		$this->triggerEvent('onAfterRender', array('app' => $this));
	}

	/**
	 * Routing.
	 *
	 * @param string $route
	 *
	 * @throws  \LogicException
	 * @throws  \UnexpectedValueException
	 * @return  mixed
	 */
	public function route($route = null)
	{
		$route = $route ? : $this->container->get('uri')->get('route');

		$route = $this->matchRoute($route);

		$variables = $route->getVariables();
		$extra = $route->getExtra();

		// Save for input
		foreach ($variables as $name => $value)
		{
			$this->input->def($name, $value);

			// Don't forget to do an explicit set on the GET superglobal.
			$this->input->get->def($name, $value);
		}

		$controller = $extra['controller'];
		$controller = explode('::', $controller);

		$action = isset($controller[1]) ? $controller[1] : null;
		$controller = $controller[0];

		if (!class_exists($controller))
		{
			throw new \LogicException('Controller: ' . $controller . ' not found.');
		}

		$package = ArrayHelper::getValue($extra, 'package');

		// Get package
		if ($package)
		{
			$package = $this->container->get('package.' . $package);
		}

		/** @var Controller|MultiActionController $controller */
		$controller = new $controller($this->input, $this, $this->container, $package);

		if ($controller instanceof MultiActionController)
		{
			$controller->setActionName($action);
			$controller->setArguments($variables);
		}

		if (!($controller instanceof Controller))
		{
			throw new \UnexpectedValueException(
				sprintf('Controller: %s should be sub class of \Windwalker\Core\Controller\Controller', $controller)
			);
		}

		$this->config->loadArray(array('extra' => $extra));

		$this->container->set('main.controller', $controller);

		return $this;
	}

	/**
	 * render
	 *
	 * @param Controller $controller
	 *
	 * @return mixed
	 */
	public function render(Controller $controller = null)
	{
		$controller = $controller ? : $this->container->get('main.controller');

		$output = $controller->execute();

		$controller->redirect();

		return $output;
	}

	/**
	 * matchRoute
	 *
	 * @param string $route
	 *
	 * @return  Route
	 */
	public function matchRoute($route = null)
	{
		/** @var \Windwalker\Core\Router\RestfulRouter $router */
		$router = $this->getRouter();

		$method = $this->input->get('_method') ? : $this->input->getMethod();

		// Prepare option data
		$http = 'http';

		if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off'))
		{
			$http .= 's';
		}

		$options = array(
			'scheme' => $http,
			'host' => $_SERVER['HTTP_HOST'],
			'port' => $_SERVER['SERVER_PORT']
		);

		return $router->match($route, $method, $options);
	}

	/**
	 * getRouter
	 *
	 * @param boolean $new
	 *
	 * @return \Windwalker\Router\Router
	 */
	public function getRouter($new = false)
	{
		static $registered = false;

		$router = $this->container->get('system.router', $new);

		if (!$registered || $new)
		{
			$this->registerRouting($router);

			$registered = true;
		}

		return $router;
	}

	/**
	 * loadRouter
	 *
	 * @param Router $router
	 *
	 * @return Router
	 */
	public function registerRouting(Router $router)
	{
		$routes = $this->loadRoutingConfiguration();

		// Replace package routing
		foreach ($routes as $name => $route)
		{
			if (!isset($route['package']))
			{
				continue;
			}

			$pattern = ArrayHelper::getValue($route, 'pattern');

			$this->loadPackageRouting($routes, $route['package'], $pattern);

			unset($routes[$name]);
		}

		// Register routes
		foreach ($routes as $name => $route)
		{
			$pattern = ArrayHelper::getValue($route, 'pattern');
			$variables = ArrayHelper::getValue($route, 'variables', array());
			$allowMethods = ArrayHelper::getValue($route, 'method', array());

			if (isset($route['controller']))
			{
				$route['extra']['controller'] = $route['controller'];
			}

			if (isset($route['action']))
			{
				$route['extra']['action'] = $route['action'];
			}

			$router->addRoute(new Route($name, $pattern, $variables, $allowMethods, $route));
		}

		return $router;
	}

	/**
	 * loadRoutingFromPackages
	 *
	 * @param array  $routing
	 * @param string $packageName
	 * @param string $pattern
	 *
	 * @return array
	 * @internal param string $prefix
	 */
	public function loadPackageRouting(&$routing, $packageName, $pattern)
	{
		$package = $this->getPackage($packageName);

		if (!$package || !$package->isEnabled())
		{
			return $routing;
		}

		/** @var AbstractPackage $class */
		$routes = $package->loadRouting();

		foreach ((array) $routes as $key => $route)
		{
			$route['pattern'] = rtrim($pattern, '/ ') . '/' . ltrim($route['pattern'], '/ ');

			$route['pattern'] = '/' . ltrim($route['pattern'], '/ ');

			$route['extra']['package'] = $package->getName();

			$routing[$package->getName() . ':' . $key] = $route;
		}

		return $routing;
	}

	/**
	 * loadConfiguration
	 *
	 * @param Registry $config
	 *
	 * @return  void
	 */
	protected function loadConfiguration(Registry $config)
	{
	}

	/**
	 * loadRoutingConfiguration
	 *
	 * @return  mixed
	 */
	protected function loadRoutingConfiguration()
	{
		return array();
	}

	/**
	 * addFlash
	 *
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  static
	 */
	public function addFlash($msg, $type = 'info')
	{
		/** @var \Windwalker\Session\Session $session */
		$session = $this->container->get('system.session');

		$session->getFlashBag()->add($msg, $type);

		return $this;
	}

	/**
	 * Redirect to another URL.
	 *
	 * If the headers have not been sent the redirect will be accomplished using a "301 Moved Permanently"
	 * or "303 See Other" code in the header pointing to the new location. If the headers have already been
	 * sent this will be accomplished using a JavaScript statement.
	 *
	 * @param   string   $url    The URL to redirect to. Can only be http/https URL
	 * @param   boolean  $moved  True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function redirect($url, $moved = false)
	{
		$this->triggerEvent('onBeforeRedirect');

		// Init Uri
		$this->container->get('uri');

		parent::redirect($url, $moved);
	}

	/**
	 * initUri
	 *
	 * @param string $uri
	 *
	 * @return object
	 */
	public function initUri($uri = null)
	{
		static $inited = false;

		if ($inited)
		{
			return $this->get('uri');
		}

		$this->loadSystemUris($uri);

		$inited = true;

		return $this->get('uri');
	}

	/**
	 * loadSystemUris
	 *
	 * @param   string $requestUri
	 *
	 * @return  void
	 */
	protected function loadSystemUris($requestUri = null)
	{
		parent::loadSystemUris($requestUri);

		if ($this->get('uri.script') == 'index.php')
		{
			$this->set('uri.script', null);
		}
	}

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   2.0
	 */
	public function triggerEvent($event, $args = array())
	{
		/** @var \Windwalker\Event\Dispatcher $dispatcher */
		$dispatcher = $this->container->get('system.dispatcher');

		return $dispatcher->triggerEvent($event, $args);
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage($name)
	{
		$key = 'package.' . strtolower($name);

		if ($this->container->exists($key))
		{
			return $this->container->get($key);
		}

		return null;
	}

	/**
	 * prepareSystemPath
	 *
	 * @param Registry $config
	 *
	 * @return  void
	 */
	protected function prepareSystemPath($config)
	{
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContainer($container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Method to get property Mode
	 *
	 * @return  string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Method to set property mode
	 *
	 * @param   string $mode
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;

		return $this;
	}
}
