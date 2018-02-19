<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Illuminate\View\FileViewFinder;
use Windwalker\Core\Renderer\Finder\BladeFinder;
use Windwalker\Core\Renderer\Traits\GlobalVarsTrait;
use Windwalker\Core\Renderer\Traits\PackageFinderTrait;

/**
 * The BladeRenderer class.
 *
 * @since  3.0
 */
class BladeRenderer extends \Windwalker\Renderer\BladeRenderer implements CoreRendererInterface
{
    use PackageFinderTrait;
    use GlobalVarsTrait;

    /**
     * Method to get property Finder
     *
     * @return  FileViewFinder
     */
    public function getFinder()
    {
        if (!$this->finder) {
            $this->finder = new BladeFinder($this->packageFinder, $this->getFilesystem(), $this->dumpPaths());
        }

        return $this->finder;
    }
}
