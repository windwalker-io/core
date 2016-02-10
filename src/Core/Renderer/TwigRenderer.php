<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Renderer\Finder\PackageFinderInterface;
use Windwalker\Core\Renderer\Finder\TwigFilesystemLoader;

/**
 * The TwigRenderer class.
 *
 * @since  {DEPLOY_VERSION}
 */
class TwigRenderer extends \Windwalker\Renderer\TwigRenderer implements CoreRendererInterface
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
	 * getLoader
	 *
	 * @return  \Twig_LoaderInterface
	 */
	public function getLoader()
	{
		if (!$this->loader)
		{
			$this->loader = new TwigFilesystemLoader($this->packageFinder, $this->dumpPaths(), $this->config->get('path_separator'));
		}

		return $this->loader;
	}
}
