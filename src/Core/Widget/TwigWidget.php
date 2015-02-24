<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\View\Twig\WindwalkerExtension;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\Priority;

/**
 * The TwigWidget class.
 * 
 * @since  2.0
 */
class TwigWidget extends Widget
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
	 * @param string $layout
	 * @param string $package
	 */
	public function __construct($layout, $package = null)
	{
		parent::__construct($layout, new TwigRenderer, $package);
	}

	/**
	 * render
	 *
	 * @param array $data
	 *
	 * @return  string
	 */
	public function render($data = array())
	{
		$this->renderer->getEngine()
			->addExtension(new WindwalkerExtension);

		return parent::render($data);
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
}
