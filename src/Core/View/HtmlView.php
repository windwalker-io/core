<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Filesystem\Path;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Data\Data;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Core\View\Helper\ViewHelper;

/**
 * Class HtmlView
 *
 * @property-read  ViewModel|mixed  $model   The ViewModel object.
 * @property-read  Registry         $config  Config object.
 *
 * @since 1.0
 */
class HtmlView extends \Windwalker\View\HtmlView
{
	/**
	 * @const boolean
	 */
	const DEFAULT_MODEL = true;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = null;

	/**
	 * Property package.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * Property config.
	 *
	 * @var Registry
	 */
	protected $config;

	/**
	 * Property model.
	 *
	 * @var ViewModel
	 */
	protected $model;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array             $data     The data array.
	 * @param   RendererInterface $renderer The renderer engine.
	 */
	public function __construct($data = array(), RendererInterface $renderer = null)
	{
		$this->config = new Registry;
		$this->model = new ViewModel;

		parent::__construct($data, $renderer);

		// Create PriorityQueue
		$paths = $this->renderer->getPaths();

		if (!($paths instanceof PriorityQueue))
		{
			$paths = new PriorityQueue($paths);

			$this->renderer->setPaths($paths);
		}
	}

	/**
	 * prepareRender
	 *
	 * @param   Data  $data
	 *
	 * @return  void
	 */
	protected function prepareRender($data)
	{
	}

	/**
	 * prepareData
	 *
	 * @param \Windwalker\Data\Data $data
	 *
	 * @return  void
	 */
	protected function prepareData($data)
	{
	}

	/**
	 * getData
	 *
	 * @return  \Windwalker\Data\Data
	 */
	public function getData()
	{
		if (!$this->data)
		{
			$this->data = new Data;
		}

		return $this->data;
	}

	/**
	 * setData
	 *
	 * @param   \Windwalker\Data\Data $data
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setData($data)
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * render
	 *
	 * @return  string
	 *
	 * @throws \RuntimeException
	 */
	public function render()
	{
		$this->registerPaths();

		$data = $this->getData();

		$this->prepareRender($data);

		$this->prepareData($data);

		$this->prepareGlobals($data);

		$dispatcher = $this->getPackage()->getDispatcher();

		$dispatcher->triggerEvent('onViewBeforeRender', array(
			'data' => $this->data,
			'view' => $this
		));

		$output = $this->renderer->render($this->getLayout(), (array) $data);

		$output = $this->postRender($output);

		$dispatcher->triggerEvent('onViewAfterRender', array(
			'data'   => $this->data,
			'view'   => $this,
			'output' => &$output
		));

		return $output;
	}

	/**
	 * postRender
	 *
	 * @param   string  $output
	 *
	 * @return  string
	 */
	protected function postRender($output)
	{
		return $output;
	}

	/**
	 * registerPaths
	 *
	 * @return void
	 */
	protected function registerPaths()
	{
		if ($this->config['path.registered'])
		{
			return;
		}

		/**
		 * @var PriorityQueue $paths
		 * @var Registry      $config
		 */
		$paths   = $this->renderer->getPaths();
		$config  = $this->getPackage()->getContainer()->get('config');
		$ref     = new \ReflectionClass($this);
		$package = $this->getPackage();

		if ($this->config['package.path'])
		{
			$paths->insert(Path::normalize($this->config['package.path'] . '/Templates/' . $this->getName()), Priority::LOW);
			$paths->insert(Path::normalize($this->config['package.path'] . '/Templates'), Priority::LOW);
		}
		elseif (!$package instanceof NullPackage)
		{
			$paths->insert(Path::normalize($package->getDir() . '/Templates/' . $this->getName()), Priority::LOW);
			$paths->insert(Path::normalize($package->getDir() . '/Templates'), Priority::LOW);
		}
		else
		{
			$paths->insert(Path::normalize(dirname($ref->getFileName()) . '/../../Templates/' . $this->getName()), Priority::LOW);
			$paths->insert(Path::normalize(dirname($ref->getFileName()) . '/../../Templates'), Priority::LOW);
		}

		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName() . '/' . $this->getName()), Priority::LOW - 10);
		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName()), Priority::LOW - 10);

		$paths = RendererHelper::getGlobalPaths()->merge($paths);

		$this->renderer->setPaths($paths);

		$this->config['path.registered'] = true;
	}

	/**
	 * getName
	 *
	 * @param int $backwards
	 *
	 * @return string
	 */
	public function getName($backwards = 2)
	{
		if (!$this->name)
		{
			if ($this->config['name'])
			{
				return $this->name = $this->config['name'];
			}

			$class = get_called_class();

			// If we are using this class as default view, return default name.
			if ($class == __CLASS__)
			{
				return $this->name = 'default';
			}

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
			// Get package name or guess it.
			$name = $this->config['package.name'] ? : MvcHelper::guessPackage(get_called_class(), $backwards);

			// Get package object
			if ($name)
			{
				$this->package = PackageHelper::getPackage($name);
			}

			// If package not found, use NullPackage instead.
			if (!$this->package)
			{
				$this->package = new NullPackage;

				$this->package->setName($name);
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
		$this->package = $package;

		$this->config['package.name'] = $package->getName();
		$this->config['package.path'] = $package->getDir();

		return $this;
	}

	/**
	 * prepareGlobals
	 *
	 * @param \Windwalker\Data\Data $data
	 *
	 * @return  void
	 */
	protected function prepareGlobals($data)
	{
		$data->view = new Data;

		$data->view->name = $this->getName();
		$data->view->layout = $this->getLayout();

		$data->helper = new HelperSet($this);

		$globals = ViewHelper::getGlobalVariables($this->getPackage());

		$data->bind($globals);
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
		$this->config = $config instanceof Registry ? $config : new Registry($config);

		$this->name = $this->config['name'];
		$this->package = $this->getPackage();

		return $this;
	}

	/**
	 * __get
	 *
	 * @param string $name
	 *
	 * @return  Registry
	 */
	public function __get($name)
	{
		if ($name == 'config')
		{
			return $this->config;
		}

		if ($name == 'model')
		{
			return $this->model;
		}

		return null;
	}

	/**
	 * Method to get property Model
	 *
	 * @param  string $name
	 *
	 * @return Model
	 */
	public function getModel($name = null)
	{
		return $this->model->getModel($name);
	}

	/**
	 * Method to set property model
	 *
	 * @param   Model   $model
	 * @param   bool    $default
	 *
	 * @return static Return self to support chaining.
	 */
	public function setModel(Model $model, $default = false)
	{
		$this->model->setModel($model, $default);

		return $this;
	}

	/**
	 * removeModel
	 *
	 * @param string $name
	 *
	 * @return  static
	 */
	public function removeModel($name)
	{
		$this->model->removeModel($name);

		return $this;
	}

	/**
	 * getRouter
	 *
	 * @return  \Windwalker\Core\Router\RestfulRouter
	 */
	public function getRouter()
	{
		return $this->getPackage()->getRouter();
	}
}
