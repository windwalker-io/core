<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Finder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;

/**
 * The BladeFinder class.
 *
 * @since  {DEPLOY_VERSION}
 */
class BladeFinder extends FileViewFinder
{
	/**
	 * Property finder.
	 *
	 * @var  PackageFinderInterface
	 */
	private $finder;

	/**
	 * Create a new file view loader instance.
	 *
	 * @param  PackageFinderInterface            $finder
	 * @param  \Illuminate\Filesystem\Filesystem $files
	 * @param  array                             $paths
	 * @param  array                             $extensions
	 */
	public function __construct(PackageFinderInterface $finder, Filesystem $files, array $paths, array $extensions = null)
	{
		parent::__construct($files, $paths, $extensions);

		$this->finder = $finder;
	}

	/**
	 * Get the fully qualified location of the view.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	public function find($name)
	{
		$path = $this->finder->find($name);

		if ($path)
		{
			array_unshift($this->paths, $path);
		}

		return parent::find($name);
	}
}
