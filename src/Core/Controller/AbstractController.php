<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Controller\Middleware\AbstractControllerMiddleware;
use Windwalker\Core\Frontend\Bootstrap;
use Windwalker\Core\Model\Model;
use Windwalker\Core\Mvc\ModelResolver;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\DefaultPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Core\View\AbstractView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Data\Data;
use Windwalker\DI\Container;
use Windwalker\Event\EventInterface;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\Http\Response\Response;
use Windwalker\IO\Input;
use Windwalker\Core\Ioc;
use Windwalker\Middleware\Chain\ChainBuilder;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The Controller class.
 *
 * @property-read  Registry        $config  Config object.
 * @property-read  WebApplication  $app     The application object.
 * @property-read  Input           $input   The input object.
 * @property-read  PackageRouter   $router  Router of this package.
 *
 * @since  2.0
 */
abstract class AbstractController implements EventTriggerableInterface, \Serializable
{
	use BootableTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name;

	/**
	 * Property input.
	 *
	 * @var  Input
	 */
	protected $input;

	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * Property package.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config;

	/**
	 * Property redirectUrl.
	 *
	 * @var  array
	 */
	protected $redirectUrl = array(
		'url' => null,
		'msg' => null,
		'type' => null,
	);

	/**
	 * Property mute.
	 *
	 * @var  boolean
	 */
	protected $mute = false;

	/**
	 * Property hmvc.
	 *
	 * @var  boolean
	 */
	protected $hmvc = false;

	/**
	 * Property request.
	 *
	 * @var  ServerRequestInterface
	 */
	protected $request;

	/**
	 * Property response.
	 *
	 * @var  ResponseInterface
	 */
	protected $response;

	/**
	 * Property middlewares.
	 *
	 * @var  AbstractControllerMiddleware[]|ChainBuilder
	 */
	protected $middlewares = [];

	/**
	 * Property successHandler.
	 *
	 * @var  callable
	 */
	protected $successHandler;

	/**
	 * Property failureHandler.
	 *
	 * @var  callable
	 */
	protected $failureHandler;

	/**
	 * Class init.
	 *
	 * @param Input           $input
	 * @param AbstractPackage $package
	 * @param Container       $container
	 */
	public function __construct(Input $input = null, AbstractPackage $package = null, Container $container = null)
	{
		$this->config = $this->getConfig();

		if ($package)
		{
			$this->setPackage($package);
		}
		else
		{
			// Guess package
			$this->getPackage();
		}

		$this->container = $container ? : $this->getContainer();
		$this->input = $input ? : $this->getInput();
		$this->app = $this->getApplication();

		$this->registerMiddlewares();

		$this->bootTraits($this);

		$this->init();
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function init()
	{
	}

	/**
	 * hmvc
	 *
	 * @param string|AbstractController $task
	 * @param Input|array               $input
	 * @param string                    $package
	 *
	 * @return mixed
	 */
	public function hmvc($task, $input = null, $package = null)
	{
		if ($task instanceof AbstractController)
		{
			if (is_array($input))
			{
				$input = new Input($input);
			}

			/** @var AbstractController $controller */
			$controller = $task->setContainer($this->container)
				->isHmvc(true)
				->setPackage($this->package)
				->setInput($input)
				->setApplication($this->app);

			$result = $controller->execute();

			$this->passRedirect($controller);

			return $result;
		}

		$package = $package ? $this->app->getPackage($package) : $this->package;

		$response = $package->execute($package->getController($task, $input), $this->getRequest(), new Response);

		$this->passRedirect($package->getCurrentController());

		return $response->getBody()->__toString();
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
	 * doExecute
	 *
	 * @return  mixed
	 */
	abstract protected function doExecute();

	/**
	 * postExecute
	 *
	 * @param mixed $result
	 *
	 * @return  mixed
	 */
	protected function postExecute($result = null)
	{
		return $result;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  mixed Return executed result.
	 *
	 * @throws  \LogicException
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$data = new Data([
			'input'    => $this->input,
			'mute'     => $this->mute,
			'hmvc'     => $this->hmvc,
			'app'      => $this->app,
			'request'  => $this->request,
			'response' => $this->response,
			'router'   => $this->router,
			'container' => $this->container,
			'package'  => $this->getPackage()
		]);

		return $this->middlewares->execute($data);
	}

	/**
	 * innerExecute
	 *
	 * @return  mixed
	 */
	protected function innerExecute()
	{
		$this->prepareExecute();

		$this->triggerEvent('onControllerBeforeExecute', array(
			'controller' => $this
		));

		$result = $this->doExecute();

		$result = $this->postExecute($result);

		$this->triggerEvent('onControllerAfterExecute', array(
			'controller' => $this,
			'result'     => &$result
		));

		return $result;
	}

	/**
	 * Method to get property SuccessHandler
	 *
	 * @return  callable
	 */
	public function getSuccessHandler()
	{
		return $this->successHandler;
	}

	/**
	 * Method to set property successHandler
	 *
	 * @param   callable $successHandler
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setSuccessHandler(callable $successHandler)
	{
		$this->successHandler = $successHandler;

		return $this;
	}

	/**
	 * Method to get property FailureHandler
	 *
	 * @return  callable
	 */
	public function getFailureHandler()
	{
		return $this->failureHandler;
	}

	/**
	 * Method to set property failureHandler
	 *
	 * @param   callable $failureHandler
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setFailureHandler(callable $failureHandler)
	{
		$this->failureHandler = $failureHandler;

		return $this;
	}

	/**
	 * delegate
	 *
	 * @param   string $task
	 *
	 * @return  mixed
	 */
	protected function delegate($task)
	{
		if (is_callable(array($this, $task)))
		{
			$args = func_get_args();

			array_shift($args);

			return call_user_func(array($this, $task), $args);
		}

		throw new \LogicException('Task: ' . $task . ' not found.');
	}

	/**
	 * renderView
	 *
	 * @param HtmlView  $view
	 * @param string    $layout
	 * @param array     $data
	 *
	 * @return string
	 */
	public function renderView($view, $layout = 'default', $data = array())
	{
		if (is_string($view))
		{
			$view = new $view;
		}

		$this->setConfig($this->config);

		foreach ($data as $key => $value)
		{
			$view[$key] = $value;
		}

		return $view->setLayout($layout)->render();
	}

	/**
	 * handleSuccess
	 *
	 * @param mixed  $data
	 * @param string $message
	 * @param string $type
	 *
	 * @return  boolean
	 */
	public function processSuccess($data = null, $message = null, $type = 'info')
	{
		return true;
	}

	/**
	 * handleFailure
	 *
	 * @param mixed  $data
	 * @param string $message
	 * @param string $type
	 *
	 * @return  boolean
	 */
	public function processFailure($data = null, $message = null, $type = 'warning')
	{
		return false;
	}

	/**
	 * getView
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool   $forceNew
	 *
	 * @return  HtmlView|AbstractView
	 */
	public function getView($name = null, $type = 'html', $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = ViewResolver::getDIKey($name . '.' . $type);

		if (!$this->container->exists($key))
		{
			// Find if package exists
			$package = $this->getPackage();

			$viewName = sprintf('%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($type));

			try
			{
				$view = $package->getMvcResolver()->getViewResolver()->create($viewName, null, $this->getConfig());
			}
			catch (\UnexpectedValueException $e)
			{
				$view = new HtmlView(null, $this->getConfig());
			}

			$config = clone $this->config;

			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}

			$this->container->share($key, $view)->alias(get_class($view), $key);
		}

		return $this->container->get($key, $forceNew);
	}

	/**
	 * getModel
	 *
	 * @param string $name
	 * @param bool   $forceNew
	 *
	 * @return  mixed
	 */
	public function getModel($name = null, $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = ModelResolver::getDIKey($name);

		if (!$this->container->exists($key))
		{
			// Find if package exists
			$package = $this->getPackage();

			$modelName = ucfirst($name) . 'Model';

			$class = $package->getMvcResolver()->resolveModel($package, $modelName);

			if (empty($class))
			{
				$ns = MvcHelper::getPackageNamespace(get_called_class());

				$class = sprintf($ns . '\Model\\' . $modelName);
			}

			if (!class_exists($class))
			{
				$class = 'Windwalker\Core\Model\Model';
			}

			$config = clone $this->config;

			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}

			$model = new $class($config);

			/** @var Model $model */
			$model->setConfig($config);

			$this->container->share($key, $model)->alias($class, $key);
		}

		return $this->container->get($key, $forceNew);
	}

	/**
	 * setRedirect
	 *
	 * @param string $url
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  static
	 */
	public function setRedirect($url, $msg = null, $type = 'info')
	{
		$this->redirectUrl = array(
			'url' => $url,
			'msg' => $msg,
			'type' => $type,
		);

		return $this;
	}

	/**
	 * Method to get property RedirectUrl
	 *
	 * @param bool $removeKey
	 *
	 * @return  array
	 */
	public function getRedirect($removeKey = false)
	{
		return $removeKey ? array_values($this->redirectUrl) : $this->redirectUrl;
	}

	/**
	 * passRedirect
	 *
	 * @param AbstractController $controller
	 *
	 * @return  static
	 */
	public function passRedirect(AbstractController $controller)
	{
		list($url, $msg, $type) = $controller->getRedirect(true);

		$this->setRedirect($url, $msg, $type);

		return $this;
	}

	/**
	 * redirect
	 *
	 * @param string $url
	 * @param string $msg
	 * @param string $type
	 *
	 * @return  void
	 */
	public function redirect($url = null, $msg = null, $type = 'info')
	{
		if ($this->isHmvc())
		{
			return;
		}

		if (!$this->app)
		{
			return;
		}

		if (!$url)
		{
			list($url, $msg, $type) = array_values($this->redirectUrl);
		}

		if (!$url)
		{
			return;
		}

		if ($msg)
		{
			$this->addMessage($msg, $type);
		}

		$this->app->redirect($url, $this->response->getStatusCode());
	}

	/**
	 * addMessage
	 *
	 * @param string $messages
	 * @param string $type
	 *
	 * @return  static
	 */
	public function addMessage($messages, $type = Bootstrap::MSG_INFO)
	{
		if (!$this->mute)
		{
			$this->app->addMessage($messages, $type);
		}

		return $this;
	}

	/**
	 * mute
	 *
	 * @param bool $bool
	 *
	 * @return  static
	 */
	public function mute($bool = true)
	{
		$this->mute = $bool;

		return $this;
	}

	/**
	 * isMute
	 *
	 * @return  bool
	 */
	public function isMute()
	{
		return $this->mute;
	}

	/**
	 * Method to get property Package
	 *
	 * @param int $backwards
	 *
	 * @return AbstractPackage
	 */
	public function getPackage($backwards = 4)
	{
		if (!$this->package || $this->package instanceof DefaultPackage)
		{
			$package = null;

			// Guess package name.
			$name = MvcHelper::guessPackage(get_called_class(), $backwards);

			// Get package object.
			if ($name)
			{
				$package = PackageHelper::getPackage(strtolower($name));
			}

			// If name not found, find class.
			if (!$package)
			{
				$packages = PackageHelper::getPackages();

				foreach ($packages as $pkgObject)
				{
					$packageClass = ReflectionHelper::getShortName($pkgObject);

					if (strpos($packageClass, ucfirst($name)) === 0)
					{
						$package = $pkgObject;

						break;
					}
				}
			}

			// If package not found, use NullPackage instead.
			if (!$package)
			{
				$ref = new \ReflectionClass($this);
				$package = new NullPackage;

				$package->setName($name);
				$package->dir = realpath(dirname($ref->getFileName()) . str_repeat('/..', $backwards - 2));
			}

			$this->setPackage($package);
		}

		return $this->package;
	}

	/**
	 * Method to set property package
	 *
	 * @param   AbstractPackage $package
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setPackage(AbstractPackage $package)
	{
		$this->config['name'] = $this->getName();
		$this->config['package.name'] = $package->getName();
		$this->config['package.path'] = $package->getDir();

		$this->package = $package;

		return $this;
	}

	/**
	 * registerMiddlewares
	 */
	protected function registerMiddlewares()
	{
		// Do not remove this if block, otherwise will cause infinity loop.
		if ($this->middlewares instanceof ChainBuilder)
		{
			return;
		}

		$middlewares = (array) $this->middlewares;

		$this->middlewares = new ChainBuilder;
		$this->middlewares->add(function ()
		{
		    return $this->innerExecute();
		});

		krsort($middlewares);

		foreach ($middlewares as $middleware)
		{
			$this->addMiddleware($middleware);
		}
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		if (!$this->container)
		{
			$package = $this->getPackage();

			$this->container = $package->getContainer() ? : Ioc::factory();
		}

		return $this->container;
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * getApplication
	 *
	 * @return  WebApplication
	 */
	public function getApplication()
	{
		if (!$this->app)
		{
			$this->app = $this->getPackage()->app ? : $this->container->get('system.application');
		}

		return $this->app;
	}

	/**
	 * setApplication
	 *
	 * @param WebApplication $app
	 *
	 * @return  static
	 */
	public function setApplication(WebApplication $app)
	{
		$this->app = $app;

		return $this;
	}

	/**
	 * getInput
	 *
	 * @return  Input
	 */
	public function getInput()
	{
		if (!$this->input)
		{
			$this->input = new Input;
		}

		return $this->input;
	}

	/**
	 * Method to set property input
	 *
	 * @param   Input $input
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setInput(Input $input)
	{
		$this->input = $input;

		return $this;
	}

	/**
	 * Method to get property Name
	 *
	 * @param integer $backwards
	 *
	 * @return string
	 */
	public function getName($backwards = 2)
	{
		if (!$this->name)
		{
			$this->name = MvcHelper::guessName(get_called_class(), $backwards);
		}

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
	 * Method to get property Config
	 *
	 * @return  Registry
	 */
	public function getConfig()
	{
		if (!$this->config || !$this->config instanceof Registry)
		{
			$this->config = new Registry($this->config);
		}

		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Registry $config
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setConfig(Registry $config)
	{
		$this->config = $config;

		return $this;
	}

	/**
	 * getRouter
	 *
	 * @return  \Windwalker\Core\Router\PackageRouter
	 */
	public function getRouter()
	{
		return $this->package->router;
	}

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments to set in event.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   2.0
	 */
	public function triggerEvent($event, $args = array())
	{
		$container = $this->getContainer();

		if (!$container->exists('system.dispatcher'))
		{
			return null;
		}

		$dispatcher = $container->get('system.dispatcher');

		return $dispatcher->triggerEvent($event, $args);
	}

	/**
	 * Check this controller is in HMVC that we can close some behaviors.
	 *
	 * @param   boolean $boolean
	 *
	 * @return  static|boolean
	 */
	public function isHmvc($boolean = null)
	{
		if ($boolean === null)
		{
			return $this->hmvc;
		}

		$this->hmvc = (bool) $boolean;

		return $this;
	}

	/**
	 * Method to get property Request
	 *
	 * @return  ServerRequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Method to set property request
	 *
	 * @param   ServerRequestInterface $request
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRequest($request)
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Method to get property Response
	 *
	 * @return  ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Method to set property response
	 *
	 * @param   ResponseInterface $response
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setResponse($response)
	{
		$this->response = $response;

		return $this;
	}

	/**
	 * addMiddleware
	 *
	 * @param   callable|AbstractControllerMiddleware  $middleware
	 *
	 * @return  static
	 */
	public function addMiddleware($middleware)
	{
		if (is_string($middleware) && is_subclass_of($middleware, AbstractControllerMiddleware::class))
		{
			$middleware = new $middleware($this);
		}
		elseif ($middleware instanceof \Closure)
		{
			$middleware->bindTo($this);
		}

		$this->middlewares->add($middleware);

		return $this;
	}

	/**
	 * Method to get property Middlewares
	 *
	 * @return  ChainBuilder
	 */
	public function getMiddlewares()
	{
		$this->registerMiddlewares();

		return $this->middlewares;
	}

	/**
	 * Method to set property middlewares
	 *
	 * @param   ChainBuilder $middlewares
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setMiddlewares(ChainBuilder $middlewares)
	{
		$this->middlewares = $middlewares;

		return $this;
	}

	/**
	 * __get
	 *
	 * @param   string  $name
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ($name == 'input')
		{
			return $this->input;
		}

		if ($name == 'app' || $name == 'application')
		{
			return $this->app;
		}

		if ($name == 'config')
		{
			return $this->config;
		}

		if ($name == 'router')
		{
			return $this->getRouter();
		}

		throw new \OutOfRangeException('Property: ' . $name . ' not exists.');
	}

	/**
	 * Serialize the controller.
	 *
	 * @return  string  The serialized controller.
	 *
	 * @since   2.0
	 */
	public function serialize()
	{
		return serialize($this->getInput());
	}

	/**
	 * Unserialize the controller.
	 *
	 * @param   string  $input  The serialized controller.
	 *
	 * @return  AbstractController  Returns itself to support chaining.
	 */
	public function unserialize($input)
	{
		$input = unserialize($input);

		$this->setInput($input);

		return $this;
	}
}
