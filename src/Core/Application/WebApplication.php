<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Application;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Application\Web\Response;
use Windwalker\Application\Web\ResponseInterface;
use Windwalker\Controller\AbstractMultiActionController;
use Windwalker\Core\Controller\Controller;
use Windwalker\Core\Controller\MultiActionController;
use Windwalker\Core\Error\SimpleErrorHandler;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Provider\CacheProvider;
use Windwalker\Core\Provider\EventProvider;
use Windwalker\Core\Provider\RouterProvider;
use Windwalker\Core\Provider\SessionProvider;
use Windwalker\Core\Provider\SystemProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\DI\Container;
use Windwalker\Environment\Web\WebEnvironment;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\IO\Input;
use Windwalker\Registry\Registry;
use Windwalker\Router\Route;

/**
 * The WebApplication class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class WebApplication extends AbstractWebApplication implements DispatcherAwareInterface
{
	/**
	 * Property env.
	 *
	 * @var  string
	 */
	public $mode = 'prod';

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
	 * @since  {DEPLOY_VERSION}
	 */
	public $config;

	/**
	 * Class constructor.
	 *
	 * @param   Input              $input        An optional argument to provide dependency injection for the application's
	 *                                           input object.  If the argument is a Input object that object will become
	 *                                           the application's input object, otherwise a default input object is created.
	 * @param   Registry           $config       An optional argument to provide dependency injection for the application's
	 *                                           config object.  If the argument is a Registry object that object will become
	 *                                           the application's config object, otherwise a default config object is created.
	 * @param   WebEnvironment     $environment  An optional argument to provide dependency injection for the application's
	 *                                           client object.  If the argument is a Web\WebEnvironment object that object will become
	 *                                           the application's client object, otherwise a default client object is created.
	 * @param   ResponseInterface  $response     The response object.
	 */
	public function __construct(Input $input = null, Registry $config = null, WebEnvironment $environment = null, ResponseInterface $response = null)
	{
		$this->environment = $environment instanceof WebEnvironment    ? $environment : new WebEnvironment;
		$this->response    = $response    instanceof ResponseInterface ? $response    : new Response;
		$this->input       = $input       instanceof Input             ? $input       : new Input;
		$this->config      = $config      instanceof Registry          ? $config      : new Registry;

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

		$this->container = Ioc::getContainer();

		$this->loadConfiguration($this->config);

		// Set System Providers
		$this->container->registerServiceProvider(new SystemProvider($this));
		$this->container->registerServiceProvider(new WebProvider($this));

		if ($this->config->get('system.debug'))
		{
			SimpleErrorHandler::registerErrorHandler();
		}

		static::registerProviders($this->container);

		PackageHelper::registerPackages($this, $this->getPackages(), $this->container);

		$this->triggerEvent('onAfterInitialise');
	}

	/**
	 * registerProviders
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	protected static function registerProviders(Container $container)
	{
		$container
			->registerServiceProvider(new EventProvider)
			->registerServiceProvider(new RouterProvider)
			->registerServiceProvider(new CacheProvider)
			->registerServiceProvider(new SessionProvider);
	}

	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public function getPackages()
	{
		return array();
	}

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function execute()
	{
		$this->prepareExecute();

		$this->triggerEvent('onBeforeExecute');

		// Perform application routines.
		$this->doExecute();

		$this->triggerEvent('onAfterExecute');

		$this->postExecute();

		$this->triggerEvent('onBeforeRespond');

		// Send the application response.
		$this->respond();

		$this->triggerEvent('onAfterRespond');
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function doExecute()
	{
		$this->triggerEvent('onBeforeRouting');

		$controller = $this->getController();

		$this->container->share('main.controller', $controller);

		$this->triggerEvent('onAfterRouting');

		$this->triggerEvent('onBeforeRender');

		$this->setBody($controller->execute());

		$this->triggerEvent('onAfterRender');

		$controller->redirect();
	}

	/**
	 * getController
	 *
	 * @param string $route
	 *
	 * @throws  \LogicException
	 * @throws  \UnexpectedValueException
	 * @return  mixed
	 */
	public function getController($route = null)
	{
		$route = $route ? : $this->container->get('uri')->get('route');

		$variables = $this->matchRoute($route);

		// Save for input
		foreach ($variables as $name => $value)
		{
			$this->input->def($name, $value);

			// Don't forget to do an explicit set on the GET superglobal.
			$this->input->get->def($name, $value);
		}

		$controller = $variables['_controller'];

		unset($variables['_controller']);
		unset($variables['_action']);
		unset($variables['_rawRoute']);

		$controller = explode('::', $controller);

		$action = isset($controller[1]) ? $controller[1] : null;
		$controller = $controller[0];

		if (!class_exists($controller))
		{
			throw new \LogicException('Controller: ' . $controller . ' not found.');
		}

		/** @var Controller|MultiActionController $controller */
		$controller = new $controller($this->input, $this);

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

		return $controller;
	}

	/**
	 * matchRoute
	 *
	 * @param string $route
	 *
	 * @return  array|bool
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
	 * @return  \Windwalker\Router\Router
	 */
	public function getRouter()
	{
		static $registered = false;

		$router = $this->container->get('system.router');

		if (!$registered)
		{
			$routes = $this->loadRoutingConfiguration();

			// Replace package routing
			foreach ($routes as $name => $route)
			{
				if (!isset($route['package']))
				{
					continue;
				}

				$pattern = isset($route['pattern']) ? $route['pattern'] : null;

				$this->loadPackageRouting($routes, $route['package'], $name, $pattern);

				unset($routes[$name]);
			}

			// Register routes
			foreach ($routes as $name => $route)
			{
				$pattern = isset($route['pattern']) ? $route['pattern'] : null;
				$variables = isset($route['variables']) ? $route['variables'] : array();
				$allowMethods = isset($route['method']) ? $route['method'] : array();

				if (isset($route['controller']))
				{
					$variables['_controller'] = $route['controller'];
				}

				if (isset($route['action']))
				{
					$variables['_action'] = $route['action'];
				}

				$router->addRoute(new Route($name, $pattern, $variables, $allowMethods, $route));
			}

			$registered = true;
		}

		return $router;
	}

	/**
	 * loadRoutingFromPackages
	 *
	 * @return  array
	 */
	protected function loadPackageRouting(&$routing, $package, $prefix, $pattern)
	{
		$package = $this->config->get('package.' . $package);

		$class = $package->class;

		/** @var AbstractPackage $class */
		$routes = $class::loadRouting();

		foreach ((array) $routes as $key => $route)
		{
			$route['pattern'] = rtrim($pattern, '/ ') . '/' . ltrim($route['pattern'], '/ ');

			$route['pattern'] = ltrim($route['pattern'], '/ ');

			$routing[$prefix . ':' . $key] = $route;
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
		$session = Ioc::getSession();

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
	 * @since   {DEPLOY_VERSION}
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
	 * @return  object
	 */
	public function initUri()
	{
		static $inited = false;

		if ($inited)
		{
			return $this->get('uri');
		}

		$this->loadSystemUris();

		$inited = true;

		return $this->get('uri');
	}

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function triggerEvent($event, $args = array())
	{
		/** @var \Windwalker\Event\Dispatcher $dispatcher */
		$dispatcher = $this->container->get('system.dispatcher');

		$dispatcher->triggerEvent($event, $args);

		return $this;
	}

	/**
	 * prepareSystemPath
	 *
	 * @param Registry $config
	 *
	 * @return  void
	 */
	public static function prepareSystemPath(Registry $config)
	{
	}
}
 