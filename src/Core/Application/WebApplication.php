<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Application;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Application\Web\Response;
use Windwalker\Application\Web\ResponseInterface;
use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Provider;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\Router\Router;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Web\WebEnvironment;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\IO\Input;
use Windwalker\Registry\Registry;
use Windwalker\Router\Route;
use Windwalker\Session\Session;
use Windwalker\String\StringNormalise;
use Windwalker\Uri\UriHelper;
use Windwalker\Utilities\ArrayHelper;

/**
 * The WebApplication class.
 *
 * @property-read  Session  $session
 * @property-read  Router   $router
 * 
 * @since  2.0
 */
class WebApplication extends AbstractWebApplication implements WindwalkerApplicationInterface, DispatcherAwareInterface
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'windwalker';

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
	protected $config;

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
	public function __construct(Container $container = null, Input $input = null, $config = null,
		WebEnvironment $environment = null, ResponseInterface $response = null)
	{
		$this->environment = $environment instanceof WebEnvironment    ? $environment : new WebEnvironment;
		$this->response    = $response    instanceof ResponseInterface ? $response    : new Response;
		$this->input       = $input       instanceof Input             ? $input       : new Input;
		$this->config      = $config      instanceof Registry          ? $config      : new Registry($config);
		$this->container   = $container   instanceof Container         ? $container   : new Container;

		$this->name = $this->config->get('name', $this->name);

		Ioc::setContainer($this->name, $this->container);

		$this->set('execution.start', microtime(true));
		$this->set('execution.memory', memory_get_usage(false));

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
		$this->container->registerServiceProvider(new Provider\SystemProvider($this));
		$this->container->registerServiceProvider(new Provider\WebProvider($this));

		if ($this->config->get('system.debug'))
		{
			ErrorHandler::register();
		}

		$this->registerProviders($this->container);

		/** @var PackageResolver $packageResolver */
		$packageResolver = $this->container->get('package.resolver');

		$packageResolver->registerPackages($this->loadPackages());

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
	public static function loadProviders()
	{
		$providers['logger']   = new Provider\LoggerProvider;
		$providers['event']    = new Provider\EventProvider;
		$providers['database'] = new Provider\DatabaseProvider;
		$providers['router']   = new Provider\RouterProvider;
		$providers['lang']     = new Provider\LanguageProvider;
		$providers['template'] = new Provider\TemplateEngineProvider;
		$providers['cache']    = new Provider\CacheProvider;
		$providers['session']  = new Provider\SessionProvider;
		$providers['auth']     = new Provider\AuthenticationProvider;
		$providers['security'] = new Provider\SecurityProvider;

		return $providers;
	}

	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public static function loadPackages()
	{
		return array();
	}

	/**
	 * Execute the application.
	 *
	 * @return  string
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
		$output = $this->respond($this->get('return_body', false));

		$this->triggerEvent('onAfterRespond', array('app' => $this, 'output' => &$output));

		return $output;
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

		$route = rtrim($route, '/');

		$route = $this->matchRoute($route);

		$variables = $route->getVariables();
		$extra = $route->getExtra();

		// Save for input
		foreach ($variables as $name => $value)
		{
			$this->input->def($name, UriHelper::decode($value));

			// Don't forget to do an explicit set on the GET superglobal.
			$this->input->get->def($name, UriHelper::decode($value));
		}

		$package = ArrayHelper::getValue($extra, 'package');

		// Get package
		/** @var AbstractPackage $package */
		$package = $this->container->get('package.resolver')->resolvePackage($package);

		$package->setTask(ArrayHelper::getValue($extra, 'controller'));
		$package->setVariables($variables);

		$this->config['route.extra']   = $extra;
		$this->config['route.matched'] = $route->getName();
		$this->config['route.package'] = $package ? $package->getName() : null;

		$this->container->share('current.package', $package);
		$this->container->share('current.route', $route);

		return $this;
	}

	/**
	 * render
	 *
	 * @param AbstractPackage $package
	 *
	 * @return mixed
	 */
	public function render(AbstractPackage $package = null)
	{
		/** @var AbstractPackage $package */
		$package = $package ? : $this->container->get('current.package');

		$output = $package->execute();

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

		// Handle X-HTTP-Method-Override
		$headers = getallheaders();

		if (isset($headers['X-HTTP-Method-Override']))
		{
			$method = $headers['X-HTTP-Method-Override'];
		}
		else
		{
			$method = $this->input->get('_method') ? : $this->input->getMethod();
		}

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

		try
		{
			return $router->match($route, $method, $options);
		}
		// Auto routing
		catch (RouteNotFoundException $e)
		{
			$route = explode('/', $route);
			$controller = array_pop($route);

			$class = StringNormalise::toClassNamespace(
				sprintf(
					'%s\Controller\%s\%s',
					implode($route, '\\'),
					ucfirst($controller),
					$router->fetchControllerSuffix($method)
				)
			);

			if (!class_exists($class))
			{
				throw $e;
			}

			$matched = new Route(implode($route, '.') . ':' . $controller, implode($route, '/'));

			$matched->setExtra(array(
				'controller' => $class
			));

			return $matched;
		}
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

		// @TODO Move all routing rules to listeners.

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

		$this->triggerEvent('onAfterLoadPackagesRouting', array('router' => $router, 'app' => $this));

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

			if (isset($route['hook']))
			{
				$route['extra']['hook'] = $route['hook'];
			}

			$router->addRoute(new Route($name, $pattern, $variables, $allowMethods, $route));
		}

		$this->triggerEvent('onRegisterRouting', array('router' => $router, 'app' => $this));

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
			$route['pattern'] = rtrim($pattern, '/ ') . $route['pattern'];

			$route['pattern'] = '/' . ltrim($route['pattern'], '/ ');

			$route['extra']['package'] = $package->getName();

			$routing[$package->getName() . '@' . $key] = $route;
		}

		return $routing;
	}

	/**
	 * getPackage
	 *
	 * @param string $package
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage($package = null)
	{
		if (!$package)
		{
			if ($this->container->exists('current.package'))
			{
				return $this->container->get('current.package');
			}

			return null;
		}

		return $this->container->get('package.resolver')->getPackage($package);
	}

	/**
	 * addPackage
	 *
	 * @param string          $name
	 * @param AbstractPackage $package
	 *
	 * @return  static
	 */
	public function addPackage($name, AbstractPackage $package)
	{
		$this->container->get('package.resolver')->addPackage($name, $package);

		return $this;
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
		$this->triggerEvent('onBeforeRedirect', array(
			'app'   => $this,
			'url'   => &$url,
			'moved' => &$moved
		));

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
	 * getSession
	 *
	 * @return  Session
	 */
	public function getSession()
	{
		return $this->container->get('system.session');
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

	/**
	 * getDispatcher
	 *
	 * @return  DispatcherInterface
	 */
	public function getDispatcher()
	{
		return $this->container->get('system.dispatcher');
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
		$this->container->share('system.dispatcher', $dispatcher);

		return $this;
	}

	/**
	 * Method to get property Name
	 *
	 * @return  string
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
	 * is utilized for reading data from inaccessible members.
	 *
	 * @param   $name  string
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ($name == 'session')
		{
			return $this->getSession();
		}

		return parent::__get($name);
	}
}
