<?php
/**
 * Part of auth project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Ioc;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Data\Data;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Core\View\Helper\ViewHelper;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * Class HtmlView
 *
 * @since 1.0
 */
class HtmlView extends \Windwalker\View\HtmlView
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = null;

	/**
	 * Property package.
	 *
	 * @var  string
	 */
	protected $package;

	/**
	 * Property config.
	 *
	 * @var Registry
	 */
	protected $config;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   Registry|array    $config   View config.
	 * @param   array             $data     The data array.
	 * @param   RendererInterface $renderer The renderer engine.
	 */
	public function __construct($config = null, $data = array(), RendererInterface $renderer = null)
	{
		$this->setConfig($config);

		parent::__construct($data, $renderer);

		$this->registerPaths();

		$this->initialise();
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
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
		$this->getName();

		$data = $this->getData();

		$this->prepareData($data);

		$this->prepareGlobals($data);

		return $this->renderer->render($this->getLayout(), (array) $data);
	}

	/**
	 * registerPaths
	 *
	 * @return  void
	 */
	protected function registerPaths()
	{
		$paths = $this->renderer->getPaths();

		$viewTmpl = dirname(ReflectionHelper::getPath($this)) . '/../../Templates/' . $this->getName();

		if (is_dir($viewTmpl))
		{
			$paths->insert(realpath($viewTmpl), Priority::NORMAL);
		}

		$paths = Priority::createQueue(
			array_merge(iterator_to_array($paths), iterator_to_array(RendererHelper::getGlobalPaths())),
			Priority::LOW
		);

		$this->renderer->setPaths($paths);
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
	 * @return string
	 */
	public function getPackage($backwards = 4)
	{
		if (!$this->package)
		{
			$this->package = MvcHelper::guessPackage(get_called_class(), $backwards = 4);
		}

		return $this->package;
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

		$data->bind(ViewHelper::getGlobalVariables());
	}

	/**
	 * Method to get property Config
	 *
	 * @return  Registry
	 */
	public function getConfig()
	{
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
}
 
