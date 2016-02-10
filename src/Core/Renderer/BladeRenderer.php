<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Illuminate\View\FileViewFinder;
use Windwalker\Core\Renderer\Finder\BladeFinder;
use Windwalker\Core\Renderer\Finder\PackageFinderInterface;

/**
 * The BladeRenderer class.
 *
 * @since  {DEPLOY_VERSION}
 */
class BladeRenderer extends \Windwalker\Renderer\BladeRenderer implements CoreRendererInterface
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
	 * Method to get property Finder
	 *
	 * @return  FileViewFinder
	 */
	public function getFinder()
	{
		if (!$this->finder)
		{
			$this->finder = new BladeFinder($this->packageFinder, $this->getFilesystem(), $this->dumpPaths());
		}

		return $this->finder;
	}
}
