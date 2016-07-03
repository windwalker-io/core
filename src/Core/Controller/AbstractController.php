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
use Windwalker\Core\Model\ModelRepository;
use Windwalker\Core\Mvc\ModelResolver;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\DefaultPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Core\View\AbstractView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Core\View\LayoutRenderableInterface;
use Windwalker\Data\Data;
use Windwalker\DI\Container;
use Windwalker\Event\EventInterface;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\Http\Response\Response;
use Windwalker\IO\Input;
use Windwalker\Core\Ioc;
use Windwalker\Middleware\Chain\ChainBuilder;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The Controller class.
 *
 * @property-read  Structure      $config  Config object.
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
	 * Controller name (Name of this MVC group).
	 *
	 * @var  string
	 */
	protected $name;

	/**
	 * The request input object.
	 *
	 * @var  Input
	 */
	protected $input;

	/**
	 * Application object.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * DI Container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * Package object.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * Config object.
	 *
	 * @var  Structure
	 */
	protected $config;

	/**
	 * The redirect url.
	 *
	 * @var  array
	 */
	protected $redirectUrl = array(
		'url' => null,
		'msg' => null,
		'type' => null,
	);

	/**
	 * If set to TRUE, all message will not set to session.
	 *
	 * @var  boolean
	 */
	protected $mute = false;

	/**
	 * If this controller in HMVC mode?.
	 *
	 * All redirect will disable in HMVC mode.
	 *
	 * @var  boolean
	 */
	protected $hmvc = false;

	/**
	 * Psr7 Server Request object.
	 *
	 * @var  ServerRequestInterface
	 */
	protected $request;

	/**
	 * Psr7 response object.
	 *
	 * @var  ResponseInterface
	 */
	protected $response;

	/**
	 * The controller middleware object.
	 *
	 * @var  AbstractControllerMiddleware[]|PriorityQueue
	 */
	protected $middlewares = [];

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

		// Prepare middlewares
		$this->middlewares = (new PriorityQueue)->insertArray((array) $this->middlewares);

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
	 * Run HMVC to fetch content from other controller.
	 *
	 * @param string|AbstractController $task    The task to exeiocute, must be controller object or string.
	 *                                           The string format is `Name\ActionController`
	 *                                           example: `Page\GetController`
	 * @param Input|array               $input   The input for this task, can be array or Input object.
	 * @param string                    $package The package for this controller, can be string or AbstractPackage.
	 *
	 * @return mixed
	 */
	public function hmvc($task, $input = null, $package = null)
	{
		// If task is controller object, just execute it.
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

		// If task is string, find controller from package
		$package = $package ? $this->app->getPackage($package) : $this->package;

		$response = $package->execute($package->getController($task, $input), $this->getRequest(), new Response, true);

		$this->passRedirect($package->getCurrentController());

		return $response->getBody()->__toString();
	}

	/**
	 * Prepare execute hook.
	 *
	 * @return  void
	 */
	protected function prepareExecute()
	{
	}

	/**
	 * Do execute action.
	 *
	 * @return  mixed
	 */
	abstract protected function doExecute();

	/**
	 * Post execute hook.
	 *
	 * @param mixed $result The result content to return, can be any value or boolean.
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
		// @ prepare hook
		$this->prepareExecute();

		// @ before event
		$this->triggerEvent('onControllerBeforeExecute', array(
			'controller' => $this
		));

		// Prepare controller data for middlewares.
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

		// Prepare the last middleware, the last middleware is the real logic of this controller self.
		$chain = $this->getMiddlewareChain()->setEndMiddleware(function ()
		{
			return $this->innerExecute();
		});

		// Do execute, run middlewares.
		$result = $chain->execute($data);

		// @ post hook
		$result = $this->postExecute($result);

		// @ post event
		$this->triggerEvent('onControllerAfterExecute', array(
			'controller' => $this,
			'result'     => &$result
		));

		return $result;
	}

	/**
	 * This method will ba a middleware in the execution chain.
	 *
	 * @return  mixed
	 *
	 * @throws \Exception
	 * @throws \Throwable
	 */
	protected function innerExecute()
	{
		try
		{
			// Call the doExecute() method, this method is a placeholder to write your own controller logic.
			$result = $this->doExecute();
		}
		catch (\Exception $e)
		{
			// You can do some error handling in processFailure(), for example: rollback the transaction.
			$this->processFailure($e->getMessage(), Bootstrap::MSG_DANGER);

			throw $e;
		}
		catch (\Throwable $e)
		{
			// You can do some error handling in processFailure(), for example: rollback the transaction.
			$this->processFailure();

			throw $e;
		}

		if ($result !== false)
		{
			// If result not FALSE, run processSuccess() to do some extra logic.
			$this->processSuccess();
		}
		else
		{
			// You can do some error handling in processFailure(), for example: rollback the transaction.
			$this->processFailure();
		}

		// Now we return result to package that it will handle response.
		return $result;
	}

	/**
	 * Method to easily distribute task to other methods that we can process different tasks.
	 *
	 * Example in doExecute():
	 * ```
	 * return $this->delegate($this->input->get('task'), `arg1`, `arg2`);
	 *
	 * // OR
	 *
	 * return $this->delegate([$object, 'method'], `arg1`, `arg2`);
	 * ```
	 *
	 * @param   string $task  The task name.
	 * @param   array  $args  The arguments we provides.
	 *
	 * @return mixed
	 * @throws \LogicException
	 */
	protected function delegate($task, ...$args)
	{
		if (is_callable(array($this, $task)))
		{
			return $this->$task(...$args);
		}

		if (is_callable($task))
		{
			return $task(...$args);
		}

		throw new \LogicException('Task: ' . $task . ' not found.');
	}

	/**
	 * Method to easily render view.
	 *
	 * @param LayoutRenderableInterface|string $view   The view name or object.
	 * @param string                           $layout The layout to render.
	 * @param string                           $engine The engine of template.
	 * @param array                            $data   The data to set in view.
	 *
	 * @return string
	 * @throws \LogicException
	 */
	public function renderView($view, $layout = 'default', $engine = 'php', array $data = array())
	{
		if (is_string($view))
		{
			$view = class_exists($view) ? new $view : $this->getView($view, 'html', $engine);
		}

		if (!$view instanceof LayoutRenderableInterface)
		{
			throw new \LogicException('View should be instance of: ' . LayoutRenderableInterface::class);
		}

		$this->setConfig($this->config);

		foreach ($data as $key => $value)
		{
			$view[$key] = $value;
		}

		if ($layout !== null)
		{
			$view->setLayout($layout);
		}

		return $view->render();
	}

	/**
	 * Process success.
	 *
	 * @param string $message Success message.
	 * @param string $type    The message type.
	 *
	 * @return bool
	 */
	public function processSuccess($message = null, $type = 'info')
	{
		return true;
	}

	/**
	 * Process failure.
	 *
	 * @param string $message Failure message.
	 * @param string $type    The message type.
	 *
	 * @return bool
	 */
	public function processFailure($message = null, $type = 'warning')
	{
		return false;
	}

	/**
	 * Get view object.
	 *
	 * @param string $name     The view name.
	 * @param string $format   The view foramt.
	 * @param string $engine   The renderer template engine.
	 * @param bool   $forceNew The Force create new instance.
	 *
	 * @return AbstractView|HtmlView
	 * @throws \UnexpectedValueException
	 *
	 * @throws \DomainException
	 */
	public function getView($name = null, $format = 'html', $engine = null, $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = ViewResolver::getDIKey($name . '.' . strtolower($format));

		// Let's prepare controller getter.
		if (!$this->container->exists($key))
		{
			// Find if package exists
			$package = $this->getPackage();

			$viewName = sprintf('%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($format));

			$config = clone $this->config;

			/*
			 * If the name of this view not same as this controller, we don't pass name into it,
			 * so that the view will keep it's own name.
			 * Otherwise we override the name in view config with ours.
			 */
			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}
			
			try
			{
				$view = $package->getMvcResolver()->getViewResolver()->create($viewName, [], $config, $engine);
			}
			catch (\DomainException $e)
			{
				// If format is html or NULL, we return HtmlView as default.
				if ($format === 'html' || !$format)
				{
					$view = new HtmlView([], $config, $engine);
				}
				// Otherwise we throw exception to notice developers that they did something wrong.
				else
				{
					throw $e;
				}
			}

			$class = get_class($view);

			$this->container->share($class, $view)->alias($key, $class);
		}

		// Get view from controller.
		return $this->container->get($key, $forceNew);
	}

	/**
	 * getModel
	 *
	 * @param string $name
	 * @param mixed  $source
	 * @param bool   $forceNew
	 *
	 * @return ModelRepository
	 * @throws \UnexpectedValueException
	 * @throws \DomainException
	 */
	public function getModel($name = null, $source = null, $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = ModelResolver::getDIKey($name);

		// Let's prepare controller getter.
		if (!$this->container->exists($key))
		{
			// Find if package exists
			$package = $this->getPackage();

			$modelName = ucfirst($name) . 'Model';

			$config = clone $this->config;

			/*
			 * If the name of this model not same as this controller, we don't pass name into it,
			 * so that the model will keep it's own name.
			 * Otherwise we override the name in model config with ours.
			 */
			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}

			try
			{
				if ($source === null)
				{
					// If DB exists, pass it into model.
					$source = $this->container->exists('database') ? $this->container->get('database') : null;
				}

				// Use resolver to find model class and create it.
				$model = $package->getMvcResolver()->getModelResolver()->create($modelName, $config, null, $source);
			}
			catch (\DomainException $e)
			{
				$model = new ModelRepository($config);
			}

			$class = get_class($model);

			$this->container->share($class, $model)->alias($key, $class);
		}

		// Get model from controller.
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
	 * @return  ChainBuilder
	 */
	protected function getMiddlewareChain()
	{
		$middlewares = array_reverse(iterator_to_array(clone $this->middlewares));

		$chain = new ChainBuilder;

		foreach ($middlewares as $middleware)
		{
			if (is_string($middleware) && is_subclass_of($middleware, AbstractControllerMiddleware::class))
			{
				$middleware = new $middleware($this);
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
	 * @throws \UnexpectedValueException
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
	 * @return  Structure
	 */
	public function getConfig()
	{
		if (!$this->config || !$this->config instanceof Structure)
		{
			$this->config = new Structure($this->config);
		}

		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Structure $config
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setConfig(Structure $config)
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
	 * @param   int                                   $priority
	 *
	 * @return static
	 */
	public function addMiddleware($middleware, $priority = PriorityQueue::NORMAL)
	{
		$this->middlewares->insert($middleware, $priority);

		return $this;
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
