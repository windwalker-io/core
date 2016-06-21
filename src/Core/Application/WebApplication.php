<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Application\AbstractWebApplication;
use Windwalker\Core;
use Windwalker\Core\Application\Middleware\AbstractWebMiddleware;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\Environment\WebEnvironment;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\IO\PsrInput;
use Windwalker\Language\Language;
use Windwalker\Middleware\Chain\Psr7ChainBuilder;
use Windwalker\Middleware\Psr7Middleware;
use Windwalker\Middleware\Psr7InvokableInterface;
use Windwalker\Registry\Registry;
use Windwalker\Session\Session;
use Windwalker\Uri\UriData;

/**
 * The WebApplication class.
 *
 * @property-read  Container                     container
 * @property-read  Core\Logger\LoggerManager     logger
 * @property-read  PsrInput                      input
 * @property-read  UriData                       uri
 * @property-read  Core\Event\EventDispatcher    dispatcher
 * @property-read  AbstractDatabaseDriver        database
 * @property-read  Core\Router\CoreRouter        router
 * @property-read  Language                      language
 * @property-read  Core\Renderer\RendererManager renderer
 * @property-read  Core\Cache\CacheFactory       cache
 * @property-read  Session                       session
 * @property-read  Core\Mailer\MailerManager     mailer
 *
 * @since  2.0
 */
class WebApplication extends AbstractWebApplication implements WindwalkerApplicationInterface, DispatcherAwareInterface, ContainerAwareInterface
{
	use Core\WindwalkerTrait;
	use Core\Utilities\Classes\BootableTrait;
	use DispatcherAwareTrait;
	use ContainerAwareTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'web';

	/**
	 * Property configPath.
	 *
	 * @var  string
	 */
	protected $configPath;

	/**
	 * Property middlewares.
	 *
	 * @var  Psr7ChainBuilder
	 */
	protected $middlewares;

	/**
	 * Class constructor.
	 *
	 * @param   Request        $request       An optional argument to provide dependency injection for the Http request object.
	 * @param   Registry       $config        An optional argument to provide dependency injection for the application's
	 *                                        config object.
	 * @param   WebEnvironment $environment   An optional argument to provide dependency injection for the application's
	 *                                        environment object.
	 *
	 * @since   2.0
	 */
	public function __construct(Request $request = null, Registry $config = null, WebEnvironment $environment = null)
	{
		$this->config = $config instanceof Registry ? $config : new Registry($config);
		$this->name   = $this->config->get('name', $this->name);

		Core\Ioc::setProfile($this->name);

		$this->container = Core\Ioc::factory();

		parent::__construct($request, $config, $environment);
	}

	/**
	 * Custom initialisation method.
	 *
	 * Called at the end of the AbstractApplication::__construct() method.
	 * This is for developers to inject initialisation code for their application classes.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function init()
	{

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
		$this->boot();

		$this->registerMiddlewares();

		return parent::execute();
	}

	/**
	 * Method to run the application routines. Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  Response
	 *
	 * @since   2.0
	 */
	protected function doExecute()
	{
		$this->server->setHandler($this->middlewares);

		return $this->server->execute();
	}

	/**
	 * Method as the Psr7 WebHttpServer handler.
	 *
	 * @param  Request  $request  The Psr7 ServerRequest to get request params.
	 * @param  Response $response The Psr7 Response interface to prepare respond data.
	 * @param  callable $next     The next handler to support middleware pattern.
	 *
	 * @return  Response  The returned response object.
	 *
	 * @since   3.0
	 */
	public function dispatch(Request $request, Response $response, $next = null)
	{
		/** @var AbstractPackage $package */
		$package = $this->container->get('current.package');

		$response = $package->execute($request->getAttribute('_controller'), $request, $response);

		return $response;
	}

	/**
	 * registerMiddlewares
	 *
	 * @return  void
	 */
	protected function registerMiddlewares()
	{
		// Init middlewares
		$this->getMiddlewares();

		$middlewares = (array) $this->config->get('middlewares', []);

		krsort($middlewares);

		foreach ($middlewares as $middleware)
		{
			$this->addMiddleware($middleware);
		}

		// Remove closures
		$this->config->set('middlewares', null);
	}

	/**
	 * addMessage
	 *
	 * @param string|array $messages
	 * @param string       $type
	 *
	 * @return  static
	 */
	public function addMessage($messages, $type = 'info')
	{
		/** @var \Windwalker\Session\Session $session */
		$session = $this->container->get('session');

		$session->getFlashBag()->add($messages, $type);

		return $this;
	}

	/**
	 * clearMessage
	 *
	 * @return  static
	 */
	public function clearMessages()
	{
		/** @var \Windwalker\Session\Session $session */
		$session = $this->container->get('session');

		$session->getFlashBag()->clear();

		return $this;
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
	 * addMiddleware
	 *
	 * @param   callable|Psr7InvokableInterface $middleware
	 *
	 * @return  static
	 */
	public function addMiddleware($middleware)
	{
		if (is_string($middleware) && is_subclass_of($middleware, AbstractWebMiddleware::class))
		{
			$middleware = new Psr7Middleware(new $middleware($this));
		}
		elseif ($middleware instanceof \Closure)
		{
			$middleware->bindTo($this);
		}

		$this->getMiddlewares()->add($middleware);

		return $this;
	}

	/**
	 * Redirect to another URL.
	 *
	 * If the headers have not been sent the redirect will be accomplished using a "301 Moved Permanently"
	 * or "303 See Other" code in the header pointing to the new location. If the headers have already been
	 * sent this will be accomplished using a JavaScript statement.
	 *
	 * @param   string      $url  The URL to redirect to. Can only be http/https URL
	 * @param   boolean|int $code True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function redirect($url, $code = 303)
	{
		$this->triggerEvent('onBeforeRedirect', array(
			'app'  => $this,
			'url'  => &$url,
			'code' => &$code
		));

		parent::redirect($url, $code);
	}

	/**
	 * Method to get property Mode
	 *
	 * @return  string
	 */
	public function getMode()
	{
		return $this->get('system.mode');
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
		$this->set('system.mode', (string) $mode);

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
		$diMapping = [
			'input'      => 'input',
			'uri'        => 'uri',
			'dispatcher' => 'dispatcher',
			'database'   => 'database',
			'router'     => 'router',
			'language'   => 'language',
			'renderer'   => 'renderer',
			'cache'      => 'cache',
			'session'    => 'session',
			'mailer'     => 'mailer'
		];

		if (isset($diMapping[$name]))
		{
			return $this->container->get($diMapping[$name]);
		}

		$allowNames = array(
			'container'
		);

		if (in_array($name, $allowNames))
		{
			return $this->$name;
		}

		return parent::__get($name);
	}
}
