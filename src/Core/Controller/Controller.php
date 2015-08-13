<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Controller;

use Windwalker\Controller\AbstractController;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\Core\View\BladeHtmlView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Core\View\TwigHtmlView;
use Windwalker\DI\Container;
use Windwalker\IO\Input;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;
use Windwalker\View\AbstractView;

/**
 * The Controller class.
 *
 * @property-read  Registry  $config  Config object.
 * 
 * @since  2.0
 */
abstract class Controller extends AbstractController
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

		$this->container = $container ? : $this->getContainer();

		$package = $package ? : $this->getPackage();

		$this->config = $this->getConfig();
		$this->setPackage($package);

		parent::__construct($input, $app);
	}

	/**
	 * hmvc
	 *
	 * @param string|Controller $controller
	 * @param Input|array       $input
	 * @param string            $package
	 *
	 * @return mixed
	 * @internal param string|Controller $controller
	 */
	public function hmvc($task, $input = null, $package = null)
	{
		if ($task instanceof Controller)
		{
			if (is_array($input))
			{
				$input = new Input($input);
			}

			$controller = $task->setContainer($this->container)
				->setPackage($this->package)
				->setInput($input)
				->setApplication($this->app);

			return $controller->execute();
		}

		$package = $package ? $this->app->getPackage($package) : $this->package;

		return $package->execute($task, $input);
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

		$result = $this->doExecute();

		return $this->postExecute($result);
	}

	/**
	 * renderView
	 *
	 * @param HtmlView $view
	 * @param string   $layout
	 * @param array    $data
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
	 * getView
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool   $forceNew
	 *
	 * @return  HtmlView|TwigHtmlView|BladeHtmlView
	 */
	public function getView($name = null, $type = 'html', $forceNew = false)
	{
		$name = $name ? : $this->getName();

		$key = 'view.' . $name . '.' . $type;

		if (!$this->container->exists($key) || $forceNew)
		{
			$ns = MvcHelper::getPackageNamespace(get_called_class());

			$class = sprintf($ns . '\View\%s\%s%sView', ucfirst($name), ucfirst($name), ucfirst($type));

			if ($class instanceof AbstractView)
			{
				throw new \LogicException($class . ' should be child of Windwalker\View\AbstractView');
			}

			/** @var HtmlView $view */
			$view = new $class;

			$view->setConfig($this->config);

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
			$ns = MvcHelper::getPackageNamespace(get_called_class());

			$class = sprintf($ns . '\Model\%sModel', ucfirst($name), ucfirst($name));

			if ($class instanceof Model)
			{
				throw new \LogicException($class . ' should be child of Windwalker\Model\Model');
			}

			$model = new $class($this->config);

			/** @var Model $model */
			$model->setConfig($this->config);

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
		if (!$this->package)
		{
			// Guess package name.
			$name = MvcHelper::guessPackage(get_called_class(), $backwards);

			// Get package object.
			if ($name)
			{
				$this->package = PackageHelper::getPackage(strtolower($name));
			}

			// If package not found, use NullPackage instead.
			if (!$this->package)
			{
				$ref = new \ReflectionClass($this);
				$this->package = new NullPackage;

				$this->package->setName($name);
				$this->package->dir = realpath(dirname($ref->getFileName()) . str_repeat('/..', $backwards - 2));
			}
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
		$this->package = $package ? : $this->getPackage();

		$this->config['name'] = $this->getName();
		$this->config['package.name'] = $this->package->getName();
		$this->config['package.path'] = $this->package->getDir();

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
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		if (!$this->container)
		{
			$package = $this->getPackage();

			$this->container = $package->getContainer();
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
		if (!$this->config)
		{
			$this->config = new Registry;
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
}
