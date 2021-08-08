<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

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
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No route name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('route/*'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'name' => $name,
                    'ns' => StrNormalize::toClassNamespace(
                        $this->getNamesapce($io) . '/' . StrNormalize::toPascalCase($name)
                    ),
                ],
                $force
            );

        return 0;
    }
}
