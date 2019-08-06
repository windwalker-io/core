<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Utilities;

use Windwalker\Environment\PlatformHelper;
use Windwalker\Environment\ServerHelper;

/**
 * The Symlink class.
 *
 * @since  2.1.1
 */
class Symlink
{
    /**
     * make
     *
     * @param string $src
     * @param string $dest
     *
     * @return  string
     */
    public function make($src, $dest)
    {
        $windows = PlatformHelper::isWindows();

        $src  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src);
        $dest = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dest);

        if ($windows) {
            if (is_file($src)) {
                return exec("mklink /D {$dest} {$src}");
            }

            return exec("mklink /j {$dest} {$src}");
        }

        return exec("ln -s {$src} {$dest}");
    }
}
