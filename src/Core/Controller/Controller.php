<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Controller;

use Windwalker\Controller\AbstractController;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\DefaultPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\Core\View\AbstractView;
use Windwalker\Core\View\BladeHtmlView;
use Windwalker\Core\View\PhpHtmlView;
use Windwalker\Core\View\TwigHtmlView;
use Windwalker\DI\Container;
use Windwalker\Event\EventInterface;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\IO\Input;
use Windwalker\Core\Ioc;
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
abstract class Controller extends AbstractController implements EventTriggerableInterface
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = null;

	/**
	 * Property input.
	 *
	 * @var  Input
	 */
	protected $input = null;

	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app = null;

	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container = null;

	/**
	 * Property package.
	 *
	 * @var  AbstractPackage
	 */
	protected $package = null;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config = null;

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
	 * Class init.
	 *
	 * @param Input           $input
	 * @param WebApplication  $app
	 * @param Container       $container
	 * @param AbstractPackage $package
	 */
	public function __construct(Input $input = null, WebApplication $app = null, Container $container = null, AbstractPackage $package = null)
	{
		$app   = $app ? : $this->getApplication();
		$input = $input ? : $this->getInput();

		$this->config = $this->getConfig();
		$this->container = $container ? : $this->getContainer();

		// Guess package
		$this->getPackage();

		parent::__construct($input, $app);
	}

	/**
	 * hmvc
	 *
	 * @param string|Controller $task
	 * @param Input|array       $input
	 * @param string            $package
	 *
	 * @return mixed
	 */
	public function hmvc($task, $input = null, $package = null)
	{
		if ($task instanceof Controller)
		{
			if (is_array($input))
			{
				$input = new Input($input);
			}

			/** @var Controller $controller */
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

		$result = $package->execute($task, $input, true);

		$this->passRedirect($package->getCurrentController());

		return $result;
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
	 * @param PhpHtmlView $view
	 * @param string      $layout
	 * @param array       $data
	 *
	 * @return string
	 */
	public function renderView(PhpHtmlView $view, $layout = 'default', $data = array())
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
	 * getView
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool   $forceNew
	 *
	 * @return  PhpHtmlView|TwigHtmlView|BladeHtmlView|AbstractView
	 */
	public function getView($name = null, $type = 'html', $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = 'view.' . $name . '.' . $type;

		if (!$this->container->exists($key) || $forceNew)
		{
			// Find if package exists
			$package = $this->getPackage();

			if (!$package instanceof NullPackage)
			{
				$ns = ReflectionHelper::getNamespaceName($package);
			}
			else
			{
				$ns = MvcHelper::getPackageNamespace(get_called_class());
			}

			$class = sprintf($ns . '\View\%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($type));

			if (!class_exists($class))
			{
				$class = 'Windwalker\Core\View\HtmlView';
			}

			/** @var PhpHtmlView $view */
			$view = new $class;

			$config = clone $this->config;

			if ($name && strcasecmp($name, $this->getName()) !== 0)
			{
				$config['name'] = null;
			}

			$view->setConfig($config);

			$this->container->share($class, $view)->alias($key, $class);
		}

		return $this->container->get($key);
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

		$key = 'model.' . $name;

		if (!$this->container->exists($key) || $forceNew)
		{
			// Find if package exists
			$package = $this->getPackage();

			if (!$package instanceof NullPackage)
			{
				$ns = ReflectionHelper::getNamespaceName($package);
			}
			else
			{
				$ns = MvcHelper::getPackageNamespace(get_called_class());
			}

			$class = sprintf($ns . '\Model\%sModel', ucfirst($name), ucfirst($name));

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

			$this->container->share($class, $model)->alias($key, $class);
		}

		return $this->container->get($key);
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
	 * @param Controller $controller
	 *
	 * @return  static
	 */
	public function passRedirect(Controller $controller)
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
			$url = $this->redirectUrl['url'];
			$msg = $this->redirectUrl['msg'];
			$type = $this->redirectUrl['type'];
		}

		if (!$url)
		{
			return;
		}

		if ($msg)
		{
			$this->app->addFlash($msg, $type);
		}

		$this->app->redirect($url);
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
		if (!$this->mute)
		{
			$this->app->addFlash($msg, $type);
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
	public function setContainer($container)
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
			$this->app = Ioc::getApplication();
		}

		return $this->app;
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
	public function setConfig($config)
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
		return $this->package->getRouter();
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
}
