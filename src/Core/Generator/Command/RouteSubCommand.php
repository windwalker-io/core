<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;

/**
 * The GenControllerSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker route file.'
)]
class RouteSubCommand extends AbstractGeneratorSubCommand
{
    protected bool $requireDest = false;

    protected string $defaultDir = '@routes';

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        parent::configure($command);
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $name  = $io->getArgument('name');
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No route name');

            return 255;
        }

        $name = Path::clean($name, '/');
        [$path, $name] = explode('/', $name, 2) + ['', ''];
        $dir = $this->app->path($io->getOption('dir'));

        $this->codeGenerator->from($this->getViewPath('route/*'))
            ->replaceTo(
                $dir . '/' . $path,
                [
                    'name' => $name,
                ],
                $force
            );

        return 0;
    }
}
