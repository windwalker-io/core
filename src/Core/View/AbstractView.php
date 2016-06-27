<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Data\Data;
use Windwalker\Registry\Registry;
use Windwalker\String\StringNormalise;

/**
 * The AbstractView class.
 *
 * @property-read  ViewModel|mixed $model   The ViewModel object.
 * @property-read  Registry        $config  Config object.
 * @property-read  PackageRouter   $router  Router object.
 *
 * @since  2.1.5.3
 */
abstract class AbstractView implements \ArrayAccess
{
	use BootableTrait;

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
	 * Property data.
	 *
	 * @var  array
	 */
	protected $data;

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
	 * Property booted.
	 *
	 * @var  boolean
	 */
	protected $booted = false;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array  $data    The data array.
	 * @param   array  $config  The view config.
	 */
	public function __construct($data = null, $config = null)
	{
		$this->config = $config instanceof Registry ? $config : new Registry($config);
		$this->model  = new ViewModel;

		$this->setData($data);

		$this->bootTraits($this);

		$this->init();
	}

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
	}

	/**
	 * boot
	 *
	 * @return  void
	 */
	public function boot()
	{
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
	 * handleData
	 *
	 * @return  static
	 */
	public function handleData()
	{
		$data = $this->getData();

		$this->prepareRender($data);

		$this->prepareData($data);

		$this->prepareGlobals($data);

		$dispatcher = $this->getPackage()->getDispatcher();

		$dispatcher->triggerEvent('onViewAfterHandleData', array(
			'data' => &$data,
			'view' => $this
		));

		return $this;
	}

	/**
	 * processData
	 *
	 * @return  mixed
	 */
	public function getHandledData()
	{
		return $this->handleData()->getData();
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
	 * @param   array $data
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setData($data)
	{
		$this->data = $data instanceof Data ? $data : new Data($data);

		return $this;
	}

	/**
	 * get
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return  null
	 */
	public function get($key, $default = null)
	{
		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * set
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return  $this
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;

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
		$this->handleData();

		$data = $this->getData();

		$dispatcher = $this->getPackage()->getDispatcher();

		$dispatcher->triggerEvent('onViewBeforeRender', array(
			'data' => $this->data,
			'view' => $this
		));

		$output = $this->doRender($data);

		$output = $this->postRender($output);

		$dispatcher->triggerEvent('onViewAfterRender', array(
			'data'   => $this->data,
			'view'   => $this,
			'output' => &$output
		));

		return $output;
	}

	/**
	 * doRender
	 *
	 * @param  Data  $data
	 *
	 * @return string
	 */
	abstract protected function doRender($data);

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
	 * __toString
	 *
	 * @return  string
	 */
	public function __toString()
	{
		try
		{
			return (string) $this->render();
		}
		catch (\Exception $e)
		{
			trigger_error($e->getMessage(), E_ERROR);
		}
		catch (\Throwable $e)
		{
			trigger_error($e->getMessage(), E_ERROR);
		}
	}

	/**
	 * Is a property exists or not.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	/**
	 * Get a property.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  mixed The value to return.
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Set a value to property.
	 *
	 * @param mixed $offset Offset key.
	 * @param mixed $value  The value to set.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	 * Unset a property.
	 *
	 * @param mixed $offset Offset key to unset.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		if ($this->offsetExists($offset))
		{
			unset($this->data[$offset]);
		}
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
			if ($class === HtmlView::class)
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
		if ($name === 'config')
		{
			return $this->config;
		}

		if ($name === 'model')
		{
			return $this->model;
		}

		if ($name === 'router')
		{
			return $this->getRouter();
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
	 * @param   string  $customName
	 *
	 * @return static Return self to support chaining.
	 */
	public function setModel(Model $model, $default = null, $customName = null)
	{
		$this->model->setModel($model, $default, $customName);

		return $this;
	}

	/**
	 * Method to add model with name.
	 *
	 * @param string  $name
	 * @param Model   $model
	 * @param string  $default
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function addModel($name, Model $model, $default = null)
	{
		$this->model->setModel($model, $default, $name);

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
	 * @return  PackageRouter
	 */
	public function getRouter()
	{
		return $this->getPackage()->router;
	}

	/**
	 * delegate
	 *
	 * @param   string $name
	 * @param   array  $args
	 *
	 * @return   mixed
	 */
	protected function delegate($name, ...$args)
	{
		if (!count($args))
		{
			$args[] = $this->getData();
		}

		$name = str_replace('.', '_', $name);

		$name = StringNormalise::toCamelCase($name);

		if (is_callable(array($this, $name)))
		{
			return $this->$name(...$args);
		}

		return null;
	}
}
