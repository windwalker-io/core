<?php
/**
 * Part of formosa project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\View\Twig\WindwalkerExtension;
use Windwalker\Data\Data;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\Priority;

/**
 * Class TwigHtmlView
 *
 * @since 1.0
 */
class TwigHtmlView extends HtmlView
{
	/**
	 * Property renderer.
	 *
	 * @var TwigRenderer
	 */
	protected $renderer;

	/**
	 * Class init.
	 *
	 * @param array             $data
	 * @param TwigRenderer      $renderer
	 */
	public function __construct($data = array(), TwigRenderer $renderer = null)
	{
		$renderer = $renderer ? : new TwigRenderer;

		parent::__construct($data, $renderer);
	}

	/**
	 * registerPaths
	 *
	 * @return  void
	 */
	protected function registerPaths()
	{
		parent::registerPaths();

		// Remove non-existing folders because Twig will throw error.
		$paths = $this->renderer->getPaths();

		$newPaths = new \SplPriorityQueue;

		foreach ($paths as $i => $path)
		{
			if (is_dir($path))
			{
				$newPaths->insert($path, Priority::LOW - ($i * 10));
			}
		}

		$this->renderer->setPaths($newPaths);
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
		parent::prepareGlobals($data);

		$this->renderer->getEngine()
			->addExtension(new WindwalkerExtension($this));
	}
}
 