<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\View\Helper\Set\HelperSet;
use Windwalker\Core\View\Helper\ViewHelper;
use Windwalker\Core\View\Traits\LayoutRenderableTrait;
use Windwalker\Data\Data;
use Windwalker\Filesystem\Path;
use Windwalker\Renderer\AbstractRenderer;

/**
 * The AbstractHtmlView class.
 *
 * @since  {DEPLOY_VERSION}
 */
class HtmlView extends AbstractView
{
	use LayoutRenderableTrait;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   array                   $data     The data array.
	 * @param   string|AbstractRenderer $renderer The renderer engine.
	 */
	public function __construct(array $data = null, $renderer = null)
	{
		if ($renderer === null)
		{
			$renderer = $this->renderer ? : RendererHelper::ENGINE_PHP;
		}

		$this->setRenderer($renderer);

		parent::__construct($data);

		$this->data = new Data($this->data);
	}

	/**
	 * Method to escape output.
	 *
	 * @param   string  $output  The output to escape.
	 *
	 * @return  string  The escaped output.
	 *                  
	 * @since   2.0
	 */
	public function escape($output)
	{
		// Escape the output.
		return $this->getRenderer()->escape($output);
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
}
