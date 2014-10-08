<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Application;

use Windwalker\Core\Controller\Controller;
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
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;
use Windwalker\Router\Route;

/**
 * The WindwalkerWebApplication class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class WindwalkerWebApplication extends WebApplication implements DispatcherAwareInterface
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
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		$this->prepareSystemPath($this->config);

		$this->container = Ioc::getContainer();

		$this->loadConfiguration($this->config);

		if ($this->config->get('system.debug'))
		{
			SimpleErrorHandler::registerErrorHandler();
		}

		// Debug system
		if (!defined('WINDWALKER_DEBUG'))
		{
			define('WINDWALKER_DEBUG', $this->config->get('system.debug'));
		}

		$this->container->registerServiceProvider(new SystemProvider($this))
			->registerServiceProvider(new WebProvider($this));

		static::registerProviders($this->container);

		PackageHelper::registerPackages(static::getPackages(), $this, $this->container);

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
	public static function getPackages()
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
		$this->triggerEvent('onBeforeExecute');

		// Perform application routines.
		$this->doExecute();

		$this->triggerEvent('onAfterExecute');

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

		$this->triggerEvent('onAfterRouting');

		$this->triggerEvent('onBeforeRender');

		/** @var Controller $controller */
		$controller = new $controller($this->input, $this);

		if (!($controller instanceof Controller))
		{
			throw new \UnexpectedValueException(
				sprintf('Controller: %s should be sub class of \Windwalker\Core\Controller\Controller', $controller)
			);
		}

		$this->setBody($controller->execute());

		$this->triggerEvent('onAfterRender');

		$controller->redirect();
	}

	/**
	 * getController
	 *
	 * @param string $route
	 *
	 * @return  mixed
	 */
	public function getController($route = null)
	{
		$route = $route ? : $this->container->get('uri')->get('route');

		// Hack for Router bug, remove when Windwalker beta1
		$route = trim($route, '/') ? $route : 'home';

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

		$variables = $router->match($route, $method, $options);

		// Save for input
		foreach ($variables as $name => $value)
		{
			$this->input->def($name, $value);

			// Don't forget to do an explicit set on the GET superglobal.
			$this->input->get->def($name, $value);
		}

		$class = $router->getController();

		if (!class_exists($class))
		{
			throw new \LogicException('Controller: ' . $class . ' not found.');
		}

		return $class;
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

			$routes = array_merge($routes, $this->loadPackagesRouting());

			foreach ($routes as $name => $route)
			{
				$pattern = isset($route['pattern']) ? $route['pattern'] : null;
				$variables = isset($route['variables']) ? $route['variables'] : array();
				$allowMethods = isset($route['method']) ? $route['method'] : array();

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
	protected function loadPackagesRouting()
	{
		$packages = $this->config->get('packages');

		$routing = array();

		foreach ((array) $packages as $name => $package)
		{
			$class = $package['class'];

			/** @var AbstractPackage $class */
			$routes = $class::loadRouting();

			foreach ((array) $routes as $key => $route)
			{
				$routing[$name . ':' . $key] = $route;
			}
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
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function triggerEvent($event)
	{
		/** @var \Windwalker\Event\Dispatcher $dispatcher */
		$dispatcher = $this->container->get('system.dispatcher');

		$dispatcher->triggerEvent($event);

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
 