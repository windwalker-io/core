<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Core\View\Helper\ViewHelper;
use Windwalker\Filesystem\Path;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\Queue\PriorityQueue;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Data\Data;
use Windwalker\Renderer\RendererInterface;

/**
 * Class PhpHtmlView
 *
 * @since 2.1.5.3
 */
class PhpHtmlView extends AbstractView
{
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

		$renderer = $renderer ? : RendererHelper::getPhpRenderer();

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
	 * render
	 *
	 * @return  string
	 *
	 * @throws \RuntimeException
	 */
	public function render()
	{
		$this->registerPaths();

		return parent::render();
	}

	/**
	 * doRender
	 *
	 * @param  Data $data
	 *
	 * @return string
	 */
	protected function doRender($data)
	{
		return $this->renderer->render($this->getLayout(), (array) $data);
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

		$paths->insert($this->config['tmpl_path.view'], Priority::LOW);
		$paths->insert($this->config['tmpl_path.package'], Priority::LOW);

		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName() . '/' . $this->getName()), Priority::LOW - 10);
		$paths->insert(Path::normalize($config->get('path.templates') . '/' . $package->getName()), Priority::LOW - 10);

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

		$this->addPath($this->config['tmpl_path.view'] . '/' . $this->package->app->get('language.locale'), Priority::BELOW_NORMAL);
		$this->addPath($this->config['tmpl_path.view'] . '/' . $this->package->app->get('language.default'), Priority::BELOW_NORMAL);

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
}
