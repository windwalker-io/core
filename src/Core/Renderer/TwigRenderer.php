<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Renderer\Finder\TwigFilesystemLoader;
use Windwalker\Core\Renderer\Traits\GlobalVarsTrait;
use Windwalker\Core\Renderer\Traits\PackageFinderTrait;

/**
 * The TwigRenderer class.
 *
 * @since  {DEPLOY_VERSION}
 */
class TwigRenderer extends \Windwalker\Renderer\TwigRenderer implements CoreRendererInterface
{
	use PackageFinderTrait;
	use GlobalVarsTrait;

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
