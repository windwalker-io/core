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

		$this->renderer->setPaths($paths);

		$this->config['path.registered'] = true;
	}
}
