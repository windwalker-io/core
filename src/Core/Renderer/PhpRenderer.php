<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Renderer\Traits\GlobalVarsTrait;
use Windwalker\Core\Renderer\Traits\PackageFinderTrait;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The PhpRenderer class.
 *
 * @since  3.0
 */
class PhpRenderer extends \Windwalker\Renderer\PhpRenderer implements CoreRendererInterface
{
	use PackageFinderTrait;
	use GlobalVarsTrait;

	/**
	 * createSelf
	 *
	 * @return  static
	 */
	protected function createSelf()
	{
		$renderer = parent::createSelf();

		$renderer->setPackageFinder($this->packageFinder);

		return $renderer;
	}

	/**
	 * finFile
	 *
	 * @param string $file
	 * @param string $ext
	 *
	 * @return  string
	 */
	public function findFile($file, $ext = 'php')
	{
		static $paths = array();

		$path = $this->packageFinder->find($file, $ext);

		if ($path && !isset($paths[$path]))
		{
			$this->addPath($path, PriorityQueue::MAX);

			$paths[$path] = true;
		}

		return parent::findFile($file, $ext);
	}
}
