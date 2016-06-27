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
use Windwalker\Core\Router\CoreRoute;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Router\PackageRouter;
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
 * @property-read  Registry       $config  Config object.
 * @property-read  WebApplication $app     The application object.
 * @property-read  Input          $input   The input object.
 * @property-read  PackageRouter  $router  Router of this package.
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

		$response = $package->execute($package->getController($task, $input), $this->getRequest(), new Response, true);

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
	 * @return mixed Return executed result.
	 *
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function execute()
	{
		try
		{
			$this->prepareExecute();

			$this->triggerEvent('onControllerBeforeExecute', array(
				'controller' => $this
			));

			$data = new Data([
				'input'    => $this->input,
				'mute'     => $this->mute,
				'hmvc'     => $this->hmvc,
				'app'      => $this->app,
				'request'  => $this->request,
				'response' => $this->response,
				'router'    => $this->router,
				'container' => $this->container,
				'package'  => $this->getPackage()
			]);

			$data->bind(get_object_vars($this));

			$result = $this->middlewares->execute($data);

			$result = $this->postExecute($result);

			$this->triggerEvent('onControllerAfterExecute', array(
				'controller' => $this,
				'result'     => &$result
			));

			$this->processSuccess();
		}
		catch (\Exception $e)
		{
			$this->processFailure($e->getMessage(), Bootstrap::MSG_DANGER);

			throw $e;
		}
		catch (\Throwable $e)
		{
			$this->processFailure();

			throw $e;
		}

		$this->processSuccess();

		return $result;
	}

	/**
	 * innerExecute
	 *
	 * @return  mixed
	 */
	protected function innerExecute()
	{
		return $this->doExecute();
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
	 * @param   array  $args
	 *
	 * @return mixed
	 */
	protected function delegate($task, ...$args)
	{
		if (is_callable(array($this, $task)))
		{
			return $this->$task(...$args);
		}

		throw new \LogicException('Task: ' . $task . ' not found.');
	}

	/**
	 * renderView
	 *
	 * @param HtmlView $view
	 * @param string   $layout
	 * @param array    $data
	 *
	 * @return string
	 * @throws \RuntimeException
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
	 * @param string $message
	 * @param string $type
	 *
	 * @return bool
	 */
	public function processSuccess($message = null, $type = 'info')
	{
		return true;
	}

	/**
	 * handleFailure
	 *
	 * @param string $message
	 * @param string $type
	 *
	 * @return bool
	 */
	public function processFailure($message = null, $type = 'warning')
	{
		return false;
	}

	/**
	 * getView
	 *
	 * @param string $name
	 * @param string $format
	 * @param string $engine
	 * @param bool   $forceNew
	 *
	 * @return AbstractView|HtmlView
	 *
	 * @throws \UnexpectedValueException
	 */
	public function getView($name = null, $format = null, $engine = null, $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = ViewResolver::getDIKey($name . '.' . strtolower($format));

		if (!$this->container->exists($key))
		{
			// Find if package exists
			$package = $this->getPackage();

			$viewName = sprintf('%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($format));

			$config = clone $this->config;

			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}
			
			try
			{
				$view = $package->getMvcResolver()->getViewResolver()->create($viewName, [], $config, $engine);
			}
			catch (\UnexpectedValueException $e)
			{
				if ($format == 'html' || !$format)
				{
					$view = new HtmlView([], $config, $engine);
				}
				else
				{
					throw $e;
				}
			}

			$class = get_class($view);

			$this->container->share($class, $view)->alias($key, $class);
		}

		return $this->container->get($key, $forceNew);
	}

	/**
	 * getModel
	 *
	 * @param string $name
	 * @param bool   $forceNew
	 *
	 * @return  Model
	 *
	 * @throws \UnexpectedValueException
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

			$config = clone $this->config;

			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}

			try
			{
				$model = $package->getMvcResolver()->getModelResolver()->create($modelName, $config, null, $this->container->get('database'));
			}
			catch (\UnexpectedValueException $e)
			{
				$model = new Model($config);
			}

			$class = get_class($model);

			$this->container->share($class, $model)->alias($key, $class);
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
	 * @param bool $onlyValues
	 *
	 * @return  array
	 */
	public function getRedirect($onlyValues = false)
	{
		return $onlyValues ? array_values($this->redirectUrl) : $this->redirectUrl;
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
		$this->setRedirect(...$controller->getRedirect(true));

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
	 *
	 * @throws \InvalidArgumentException
	 * @throws \LogicException
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
		    return $this->doExecute();
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
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function getApplication()
	{
		if (!$this->app)
		{
			$this->app = $this->getPackage()->app ? : $this->container->get('application');
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
	 * @throws \UnexpectedValueException
	 *
	 * @since   2.0
	 */
	public function triggerEvent($event, $args = array())
	{
		$container = $this->getContainer();

		if (!$container->exists('dispatcher'))
		{
			return null;
		}

		$dispatcher = $container->get('dispatcher');

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
	 * @param   callable|AbstractControllerMiddleware $middleware
	 *
	 * @return  static
	 * @throws \InvalidArgumentException
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
	 * @param   string $name
	 *
	 * @return  mixed
	 *
	 * @throws \OutOfRangeException
	 */
	public function __get($name)
	{
		if ($name === 'input')
		{
			return $this->input;
		}

		if ($name === 'app' || $name === 'application')
		{
			return $this->app;
		}

		if ($name === 'config')
		{
			return $this->config;
		}

		if ($name === 'router')
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
