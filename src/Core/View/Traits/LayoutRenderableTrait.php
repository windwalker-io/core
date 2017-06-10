<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View\Traits;

use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Renderer\RendererManager;
use Windwalker\Filesystem\Path;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * LayoutRenderableTrait
 *
 * @since  3.0
 */
trait LayoutRenderableTrait
{
	/**
	 * Property layout.
	 *
	 * @var  string
	 */
	protected $layout = 'default';

	/**
	 * Property renderer.
	 *
	 * @var  AbstractRenderer
	 */
	protected $renderer;

	/**
	 * Property rendererManager.
	 *
	 * @var  RendererManager
	 */
	protected $rendererManager;

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
	 * @return  AbstractRenderer
	 */
	public function getRenderer()
	{
		$this->boot();

		return $this->renderer;
	}

	/**
	 * Method to set property renderer
	 *
	 * @param   AbstractRenderer|string $renderer
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRenderer($renderer)
	{
		$this->renderer = $renderer instanceof AbstractRenderer ? : $this->getRendererManager()->getRenderer((string) $renderer);

		return $this;
	}

	/**
	 * registerPaths
	 *
	 * @param bool $refresh
	 *
	 * @return static
	 */
	public function registerPaths($refresh = false)
	{
		if ($this->config['path.registered'] && !$refresh)
		{
			return $this;
		}

		/**
		 * @var PriorityQueue $paths
		 * @var Structure     $config
		 */
		$paths   = $this->getRenderer()->getPaths();
		$config  = $this->getPackage()->getContainer()->get('config');
		$ref     = new \ReflectionClass($this);
		$viewName = strtolower($this->getName());
		$package = $this->getPackage();

		if ($this->config['package.path'])
		{
			$this->config['tmpl_path.view'] = Path::normalize($this->config['package.path'] . '/Templates/' . $viewName);
			$this->config['tmpl_path.package'] = Path::normalize($this->config['package.path'] . '/Templates');
		}
		elseif (!$package instanceof NullPackage)
		{
			$this->config['tmpl_path.view'] = Path::normalize($package->getDir() . '/Templates/' . $viewName);
			$this->config['tmpl_path.package'] = Path::normalize($package->getDir() . '/Templates');
		}
		else
		{
			$this->config['tmpl_path.view'] = Path::normalize(dirname($ref->getFileName()) . '/../../Templates/' . $viewName);
			$this->config['tmpl_path.package'] = Path::normalize(dirname($ref->getFileName()) . '/../../Templates');
		}

		$paths->insert($this->config['tmpl_path.view'], PriorityQueue::LOW);
		$paths->insert($this->config['tmpl_path.package'], PriorityQueue::LOW);

		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName() . '/' . $viewName), PriorityQueue::LOW - 10);
		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName()), PriorityQueue::LOW - 10);

		$this->renderer->setPaths($paths);

		$this->config['path.registered'] = true;

		$this->registerMultilingualPaths();

		return $this;
	}

	/**
	 * registerMultilingualPaths
	 *
	 * @param bool $refresh
	 *
	 * @return static
	 */
	public function registerMultilingualPaths($refresh = false)
	{
		if ($this->config['path.multilingual_registered'] && !$refresh)
		{
			return $this;
		}

		$this->registerPaths();

		$locale = $this->getPackage()->app->get('language.locale');
		$default = $this->getPackage()->app->get('language.default');

		$this->addPath($this->config['tmpl_path.view'] . '/' . $this->getPackage()->app->get('language.locale'), PriorityQueue::BELOW_NORMAL);

		if ($locale != $default)
		{
			$this->addPath($this->config['tmpl_path.view'] . '/' . $this->getPackage()->app->get('language.default'), PriorityQueue::BELOW_NORMAL);
		}

		$this->config['path.multilingual_registered'] = true;

		return $this;
	}

	/**
	 * dumpPaths
	 *
	 * @param bool $multilingual
	 *
	 * @return  array
	 */
	public function dumpPaths($multilingual = false)
	{
		$this->registerPaths();

		if ($multilingual)
		{
			$this->registerMultilingualPaths();
		}

		return $this->renderer->dumpPaths();
	}

	/**
	 * addPath
	 *
	 * @param string $path
	 * @param int    $priority
	 *
	 * @return  static
	 */
	public function addPath($path, $priority = 100)
	{
		$renderer = $this->getRenderer();

		if ($renderer instanceof AbstractRenderer)
		{
			$renderer->addPath($path, $priority);
		}

		return $this;
	}

	/**
	 * addPaths
	 *
	 * @param array $paths
	 * @param int   $priority
	 *
	 * @return  static
	 */
	public function addPaths(array $paths, $priority = PriorityQueue::ABOVE_NORMAL)
	{
		foreach ($paths as $path)
		{
			$this->addPath($path, $priority);
		}

		return $this;
	}

	/**
	 * Method to get property RendererManager
	 *
	 * @return  RendererManager
	 */
	public function getRendererManager()
	{
		if (!$this->rendererManager)
		{
			$this->rendererManager = $this->getPackage()->getContainer()->get('renderer.manager');
		}

		return $this->rendererManager;
	}

	/**
	 * Method to set property rendererManager
	 *
	 * @param   RendererManager $rendererManager
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRendererManager($rendererManager)
	{
		$this->rendererManager = $rendererManager;

		return $this;
	}
}
