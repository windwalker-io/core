<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
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
class TwigHtmlView extends PhpHtmlView
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
		$renderer = $renderer ? : new TwigRenderer(null, array('path_separator' => '.'));

		parent::__construct($data, $renderer);
	}

//	/**
//	 * registerPaths
//	 *
//	 * @return  void
//	 */
//	protected function registerPaths()
//	{
//		parent::registerPaths();
//
//		// Remove non-existing folders because Twig will throw error.
//		$paths = $this->renderer->getPaths();
//
//		$newPaths = new \SplPriorityQueue;
//
//		foreach ($paths as $i => $path)
//		{
//			if (is_dir($path))
//			{
//				$newPaths->insert($path, Priority::LOW - ($i * 10));
//			}
//		}
//
//		$this->renderer->setPaths($newPaths);
//	}
}
