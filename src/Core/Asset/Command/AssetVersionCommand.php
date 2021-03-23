<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;

use Windwalker\Filesystem\Filesystem;

use function Windwalker\uid;

/**
 * The AssetVersionCommand class.
 */
#[CommandWrapper(description: 'Create assets version file.')]
class AssetVersionCommand implements CommandInterface
{
    /**
     * AssetVersionCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'Cache file path'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws \Exception
     */
    public function execute(IOInterface $io): int
    {
        $file = $io->getArgument('file') ?? $this->app->config('asset.version_file');
        $file = $this->app->path($file);

        Filesystem::write($file, $uid = uid());

        $io->writeln("Create Asset Version: <info>{$uid}</info> at <info>{$file}</info>");

        return 0;
    }
}
