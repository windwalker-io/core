<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Controller;

use Windwalker\Controller\AbstractController;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\View\HtmlView;
use Windwalker\DI\Container;
use Windwalker\IO\Input;
use Windwalker\Ioc;

/**
 * The Controller class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class Controller extends AbstractController
{
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
		$this->package = $package ? : new NullPackage;

		parent::__construct($input, $app);
	}

	/**
	 * hmvc
	 *
	 * @param Controller|string $controller
	 * @param Input|array       $input
	 *
	 * @return  mixed
	 */
	public function hmvc($controller, $input = null)
	{
		if (is_string($controller))
		{
			$controller = new $controller;
		}

		if (is_array($input))
		{
			$input = new Input($input);
		}

		/** @var Controller $controller */
		$controller->setContainer($this->container)
			->setPackage($this->package)
			->setApplication($this->app)
			->setInput($input);

		return $controller->execute();
	}

	/**
	 * renderView
	 *
	 * @param HtmlView $view
	 *
	 * @return  string
	 */
	public function renderView($view, $data = array(), $layout = 'default')
	{
		if (is_string($view))
		{
			$view = new $view;
		}

		foreach ($data as $key => $value)
		{
			$view->set($key, $value);
		}

		return $view->setLayout($layout)->render();
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
	 * @return  AbstractPackage
	 */
	public function getPackage()
	{
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
		$this->package = $package;

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

			$name = ($package instanceof AbstractPackage) ? $package->getName() : null;

			$this->container = Ioc::factory($name);
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
}
