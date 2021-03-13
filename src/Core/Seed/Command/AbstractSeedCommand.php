<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\Command\AbstractMigrationCommand;

/**
 * The AbstractSeedCommand class.
 */
abstract class AbstractSeedCommand extends AbstractMigrationCommand
{
    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        parent::configure($command);

        $command->addOption(
            'file',
            'f',
            InputOption::VALUE_REQUIRED,
        );
    }

    public function getSeederDefaultFile(): string
    {
        return $this->app->path('@seeders/main.php');
    }

    public function getSeederFile(IOInterface $io): string
    {
        $file = $io->getOption('file');

        if (!$file) {
            if ($dir = $io->getOption('dir')) {
                $file = $dir . '/main.php';
            } else {
                $file = $this->getSeederDefaultFile();
            }
        }

        return $file;
    }
}
