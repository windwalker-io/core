<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Cache\Command;

use Symfony\Component\Console\Attribute\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Console\IOInterface;
use Windwalker\Filesystem\Filesystem;

/**
 * The ClearCommand class.
 */
#[ConsoleCommand(
    name: 'cache:foo',
    description: 'Clear cache'
)]
class FooCommand extends CoreCommand
{
    /**
     * Configures the current command.
     */
    protected function init(): void
    {
        $this->addArgument(
            'folders',
            InputArgument::IS_ARRAY,
            'Clear this folders, if not provided, will clear all cache folder.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    protected function doExecute(IOInterface $io): mixed
    {
        $folders = $io->getArgument('folders');

        if (!count($folders)) {
            $this->clearCacheRoot();
        } else {
            foreach ($folders as $folder) {
                $this->clearCacheFolder($folder);
            }
        }

        $io->writeln('Cache cleared.');

        return 0;
    }

    /**
     * clearCacheRoot
     *
     * @return  void
     *
     * @since  3.4.6
     */
    protected function clearCacheRoot(): void
    {
        /** @var \SplFileInfo $file */
        foreach (Filesystem::items(WINDWALKER_CACHE, false) as $file) {
            if (in_array($file->getBasename(), ['.gitignore', '.htaccess', 'web.config'])) {
                continue;
            }

            Filesystem::delete($file->getPathname());

            $this->io->writeln(sprintf('[Deleted] <info>%s</info>', $file->getPathname()));
        }
    }

    /**
     * clearCacheFolder
     *
     * @param  string  $folder
     *
     * @return  void
     */
    protected function clearCacheFolder(string $folder): void
    {
        $path = WINDWALKER_CACHE . '/' . $folder;

        Filesystem::delete($path);

        $this->io->writeln(sprintf('[Deleted] <info>%s</info>', $path));
    }
}
