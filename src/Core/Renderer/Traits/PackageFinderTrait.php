<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Traits;

use Windwalker\Core\Renderer\Finder\PackageFinderInterface;

/**
 * The PackageFinderTrait class.
 *
 * @since  3.0
 */
trait PackageFinderTrait
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
}
