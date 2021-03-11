<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Core\Console\CommandInterface;
use Windwalker\Core\Console\CommandWrapper;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Console\IOInterface;
use Windwalker\Filesystem\Filesystem;

/**
 * The ClearCommand class.
 */
#[CommandWrapper(
    description: 'Clear cache'
)]
class CacheClearCommand implements CommandInterface
{
    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'folders',
            InputArgument::IS_ARRAY,
            'Clear this folders, if not provided, will clear all cache folder.'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): mixed
    {
        $folders = $io->getArgument('folders');

        if (!count($folders)) {
            $this->clearCacheRoot($io);
        } else {
            foreach ($folders as $folder) {
                $this->clearCacheFolder($folder, $io);
            }
        }

        $io->writeln('Cache cleared.');

        return 0;
    }

    /**
     * clearCacheRoot
     *
     * @param  IOInterface  $io
     *
     * @return  void
     *
     * @since  3.4.6
     */
    protected function clearCacheRoot(IOInterface $io): void
    {
        /** @var \SplFileInfo $file */
        foreach (Filesystem::items(WINDWALKER_CACHE, false) as $file) {
            if (in_array($file->getBasename(), ['.gitignore', '.htaccess', 'web.config'])) {
                continue;
            }

            Filesystem::delete($file->getPathname());

            $io->writeln(sprintf('[Deleted] <info>%s</info>', $file->getPathname()));
        }
    }

    /**
     * clearCacheFolder
     *
     * @param  string       $folder
     * @param  IOInterface  $io
     *
     * @return  void
     */
    protected function clearCacheFolder(string $folder, IOInterface $io): void
    {
        $path = WINDWALKER_CACHE . '/' . $folder;

        Filesystem::delete($path);

        $io->writeln(sprintf('[Deleted] <info>%s</info>', $path));
    }
}
