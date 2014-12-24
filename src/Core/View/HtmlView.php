<?php
/**
 * Part of auth project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\Filesystem\Path;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Data\Data;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Core\View\Helper\ViewHelper;

/**
 * Class HtmlView
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
	 * @var Model
	 */
	protected $model;

	/**
	 * Property models.
	 *
	 * @var Model[]
	 */
	protected $models;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array             $data     The data array.
	 * @param   RendererInterface $renderer The renderer engine.
	 */
	public function __construct($data = array(), RendererInterface $renderer = null)
	{
		$this->models = new Data;
		$this->config = new Registry;

		parent::__construct($data, $renderer);
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

		$this->prepareData($data);

		$this->prepareGlobals($data);

		return $this->renderer->render($this->getLayout(), (array) $data);
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

		$paths = $this->renderer->getPaths();
		$config = Ioc::getConfig();

		$ref = new \ReflectionClass($this);

		$package = $this->getPackage();

		if ($this->config['package.path'])
		{
			$paths->insert(Path::clean($this->config['package.path'] . '/Templates/' . $this->getName()), Priority::LOW);
		}
		elseif (!($package instanceof NullPackage))
		{
			$paths->insert(Path::clean($package->getDir() . '/Templates/' . $this->getName()), Priority::LOW);
		}
		else
		{
			$paths->insert(Path::clean(dirname($ref->getFileName()) . '/../../Templates/' . $this->getName()), Priority::LOW);
		}

		$paths->insert(Path::clean($config->get('path.templates') . '/' . $package->getName() . '/' . $this->getName()), Priority::LOW - 10);

		foreach (RendererHelper::getGlobalPaths() as $i => $path)
		{
			$paths->insert($path, Priority::MIN - ($i * 10));
		}

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

		$globals = ViewHelper::getGlobalVariables($this->config['package.name']);

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
		if ($name)
		{
			return $this->models[$name];
		}

		return $this->model;
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
		if ($default || !$this->model)
		{
			$this->model = $model;
		}

		$this->models[$model->getName() ? : uniqid()] = $model;

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
		// If is default model, remove it.
		if ($this->models[$name] === $this->model)
		{
			$this->model = null;
		}

		unset($this->models[$name]);

		return $this;
	}
}
