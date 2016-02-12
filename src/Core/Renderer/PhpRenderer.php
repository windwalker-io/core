<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Renderer\Finder\PackageFinderInterface;
use Windwalker\Utilities\Queue\Priority;

/**
 * The PhpRenderer class.
 *
 * @since  {DEPLOY_VERSION}
 */
class PhpRenderer extends \Windwalker\Renderer\PhpRenderer implements CoreRendererInterface
{
	/**
	 * Property finder.
	 *
	 * @var  PackageFinderInterface
	 */
	protected $packageFinder;

	/**
	 * setRendererFinder
	 *
	 * @param PackageFinderInterface $finder
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setPackageFinder(PackageFinderInterface $finder)
	{
		$this->packageFinder = $finder;

		return $this;
	}

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
			$this->addPath($path, Priority::MAX);

			$paths[$path] = true;
		}

		return parent::findFile($file, $ext);
	}
}
