<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View\Traits;

use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Filesystem\Path;
use Windwalker\Registry\Registry;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * LayoutRenderableTrait
 *
 * @since  {DEPLOY_VERSION}
 */
trait LayoutRenderableTrait
{
	/**
	 * Property renderer.
	 *
	 * @var  AbstractRenderer
	 */
	protected $renderer;

	/**
	 * Property layout.
	 *
	 * @var  string
	 */
	protected $layout = 'default';

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
		$this->renderer = $renderer instanceof AbstractRenderer ? : RendererHelper::getRenderer((string) $renderer);

		return $this;
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
			$this->config['tmpl_path.view'] = Path::normalize($this->config['package.path'] . '/Templates/' . $this->getName());
			$this->config['tmpl_path.package'] = Path::normalize($this->config['package.path'] . '/Templates');
		}
		elseif (!$package instanceof NullPackage)
		{
			$this->config['tmpl_path.view'] = Path::normalize($package->getDir() . '/Templates/' . $this->getName());
			$this->config['tmpl_path.package'] = Path::normalize($package->getDir() . '/Templates');
		}
		else
		{
			$this->config['tmpl_path.view'] = Path::normalize(dirname($ref->getFileName()) . '/../../Templates/' . $this->getName());
			$this->config['tmpl_path.package'] = Path::normalize(dirname($ref->getFileName()) . '/../../Templates');
		}

		$paths->insert($this->config['tmpl_path.view'], PriorityQueue::LOW);
		$paths->insert($this->config['tmpl_path.package'], PriorityQueue::LOW);

		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName() . '/' . $this->getName()), PriorityQueue::LOW - 10);
		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName()), PriorityQueue::LOW - 10);

		$this->renderer->setPaths($paths);

		$this->config['path.registered'] = true;
	}

	/**
	 * registerMultilingualPaths
	 *
	 * @return  void
	 */
	public function registerMultilingualPaths()
	{
		if ($this->config['path.multilingual_registered'])
		{
			return;
		}

		$this->registerPaths();

		$this->addPath($this->config['tmpl_path.view'] . '/' . $this->package->app->get('language.locale'), PriorityQueue::BELOW_NORMAL);
		$this->addPath($this->config['tmpl_path.view'] . '/' . $this->package->app->get('language.default'), PriorityQueue::BELOW_NORMAL);

		$this->config['path.multilingual_registered'] = true;
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
}