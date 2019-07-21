<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset\Command\Asset;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Utilities\Symlink;
use Windwalker\Environment\PlatformHelper;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;

/**
 * The SyncCommand class.
 *
 * @since  2.1.1
 */
class SyncCommand extends Command
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'sync';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Sync asset to main media folder';

    /**
     * initialise
     *
     * @return  void
     */
    public function init()
    {
        $this->addOption('s')
            ->alias('symbol')
            ->defaultValue(true)
            ->description('Use symbol link to link asset folders');

        $this->addOption('hard')
            ->defaultValue(false)
            ->description('Hard copy assets to media folders');

        $this->addOption('force')
            ->alias('f')
            ->defaultValue(false)
            ->description('Force replace exists link or folder.');
    }

    /**
     * doExecute
     *
     * @return  int
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function doExecute()
    {
        $hard = $this->getOption('hard');

        // Prepare path
        $name = $this->io->getArgument(0);

        /** @var WebApplication $env */
        $env = $this->getOption('env');

        $resolver = ConsoleHelper::getAllPackagesResolver($env, $this->console);

        if (!$name) {
            throw new \InvalidArgumentException('No package input.');
        }

        $package = $resolver->getPackage($name);

        if ($package) {
            $dir = $package->getDir() . '/Resources/asset';
        } else {
            throw new \InvalidArgumentException('Package ' . $name . ' not found.');
        }

        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(
                'This package has no <comment>/Resources/asset</comment> folder so nothing synced.'
            );
        }

        $folder = $this->console->get('asset.folder');
        $target = $this->getArgument(1, $name);
        $target = $this->console->get('path.public') . '/' . trim($folder, '/') . '/' . $target;

        $symlink = new Symlink();
        $force   = $this->getOption('force');

        if (is_link($target)) {
            if (!$force) {
                throw new \RuntimeException('Link ' . $target . ' already created.');
            }

            $this->out('Link file: <comment>' . $target . '</comment> exists, force replace it.');

            if (PlatformHelper::isWindows()) {
                rmdir($target);
            } else {
                File::delete($target);
            }
        }

        if ($hard) {
            $this->hardCopy($dir, $target);

            $this->out('Copy folder ' . $dir . ' to ' . $target);
        } else {
            $this->out($symlink->make($dir, $target));

            if (!PlatformHelper::isWindows()) {
                $this->out('Link success <info>' . $dir . '</info> <====> <info>' . $target . '</info>');
            }
        }

        return true;
    }

    /**
     * hardCopy
     *
     * @param string $src
     * @param string $dest
     *
     * @return  void
     */
    protected function hardCopy($src, $dest)
    {
        Folder::copy($src, $dest);
    }
}
