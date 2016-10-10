<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Finder;

use Windwalker\Edge\Loader\EdgeFileLoader;

/**
 * The EdgeLoader class.
 *
 * @since  3.0
 */
class EdgeLoader extends EdgeFileLoader
{
	/**
	 * Property finder.
	 *
	 * @var  PackageFinderInterface
	 */
	private $finder;

	/**
	 * EdgeFileLoader constructor.
	 *
	 * @param PackageFinderInterface $finder
	 * @param array                  $paths
	 */
	public function __construct(PackageFinderInterface $finder, array $paths)
	{
		$this->finder = $finder;
		
		parent::__construct($paths);
	}

	/**
	 * find
	 *
	 * @param string $key
	 *
	 * @return  string
	 */
	public function find($key)
	{
		$path = $this->finder->find($name);

		if ($path && !in_array($path, $this->paths))
		{
			array_unshift($this->paths, $path);
		}

		return parent::find($name);
	}
}
