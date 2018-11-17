<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Folder;

/**
 * The ModeCommand class.
 *
 * @since  3.0
 */
class ClearCacheCommand extends CoreCommand
{
    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'clear-cache';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Clear cache.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = '%s [<folder>] [options]';

    /**
     * Execute this command.
     *
     * @return int
     *
     * @since  2.0
     */
    protected function doExecute()
    {
        $folders = $this->io->getArguments();

        if (!count($folders)) {
            $this->clearCacheRoot();
        } else {
            foreach ($folders as $folder) {
                $this->clearCacheFolder($folder);
            }
        }

        $this->out('Cache cleared.');

        return true;
    }

    /**
     * clearCacheRoot
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function clearCacheRoot()
    {
        /** @var \SplFileInfo $file */
        foreach (Filesystem::items(WINDWALKER_CACHE, false) as $file) {
            if (in_array($file->getBasename(), ['.gitignore', '.htaccess', 'web.config'])) {
                continue;
            }

            if ($file->isDir()) {
                Folder::delete($file->getPathname());
            } else {
                File::delete($file->getPathname());
            }

            $this->out(sprintf('[Deleted] <info>%s</info>', $file->getPathname()));
        }
    }

    /**
     * clearCacheFolder
     *
     * @param string $folder
     *
     * @return  void
     */
    protected function clearCacheFolder($folder)
    {
        $path = WINDWALKER_CACHE . '/' . $folder;

        if (is_dir($path)) {
            Folder::delete($path);
        } elseif (is_file($path)) {
            File::delete($path);
        }

        $this->out(sprintf('[Deleted] <info>%s</info>', $path));
    }
}
