<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Data\Data;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Utilities\Queue\Priority;

/**
 * The Widget class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class Widget implements WidgetInterface
{
	/**
	 * Property renderer.
	 *
	 * @var  RendererInterface
	 */
	protected $renderer;

	/**
	 * Property layout.
	 *
	 * @var string
	 */
	protected $layout;

	/**
	 * Property pathRegistered.
	 *
	 * @var  bool
	 */
	protected $pathRegistered = false;

	/**
	 * Property debug.
	 *
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * Class init.
	 *
	 * @param string              $layout
	 * @param RendererInterface $renderer
	 */
	public function __construct($layout, RendererInterface $renderer = null)
	{
		$this->layout = $layout;
		$this->renderer = $renderer ? : new PhpRenderer;

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
	 * render
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function render($data = array())
	{
		$this->registerPaths();

		$data = new Data($data);

		$data->layout = $this->layout;
		$data->renderer = get_class($this->renderer);

		if ($this->isDebug())
		{
			$data->paths = iterator_to_array(clone $this->getPaths());
		}

		return $this->renderer->render($this->layout, $data);
	}

	/**
	 * Method to get property Layout
	 *
	 * @return  string
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Method to set property layout
	 *
	 * @param   string $layout
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setLayout($layout)
	{
		$this->layout = $layout;

		return $this;
	}

	/**
	 * Method to get property Renderer
	 *
	 * @return  RendererInterface
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * Method to set property renderer
	 *
	 * @param   RendererInterface $renderer
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRenderer(RendererInterface $renderer)
	{
		$this->renderer = $renderer;

		return $this;
	}

	/**
	 * registerPaths
	 *
	 * @return  static
	 */
	protected function registerPaths()
	{
		if (!$this->pathRegistered)
		{
			foreach (RendererHelper::getGlobalPaths() as $i => $path)
			{
				$this->renderer->addPath($path, Priority::LOW - (10 * $i));
			}

			$this->pathRegistered = true;
		}

		return $this;
	}

	/**
	 * addPath
	 *
	 * @param string  $path
	 * @param integer $priority
	 *
	 * @return  static
	 */
	public function addPath($path, $priority = Priority::NORMAL)
	{
		$this->renderer->addPath($path, $priority);

		return $this;
	}

	/**
	 * getPaths
	 *
	 * @return  \SplPriorityQueue
	 */
	public function getPaths()
	{
		return $this->renderer->getPaths();
	}

	/**
	 * setPaths
	 *
	 * @param array|\SplPriorityQueue $paths
	 *
	 * @return  static
	 */
	public function setPaths($paths)
	{
		$this->renderer->setPaths($paths);

		return $this;
	}

	/**
	 * Method to get property Debug
	 *
	 * @return  boolean
	 */
	public function isDebug()
	{
		return $this->debug;
	}

	/**
	 * Method to set property debug
	 *
	 * @param   boolean $debug
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;

		return $this;
	}
}
