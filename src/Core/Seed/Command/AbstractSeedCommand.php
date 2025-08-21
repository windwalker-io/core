<?php

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
            null,
            InputOption::VALUE_REQUIRED,
        );
    }

    public function getSeederDefaultFile(): string
    {
        $path = $this->app->path('@seeders/main.seeder.php');

        if (is_file($path)) {
            return $path;
        }

        // B/C
        return $this->app->path('@seeders/main.php');
    }

    public function getSeederFile(IOInterface $io): string
    {
        $file = $io->getOption('file');

        if (!$file) {
            if ($dir = $io->getOption('dir')) {
                $file = $dir . '/main.seeder.php';

                if (!is_file($file)) {
                    // B/C
                    $file = $dir . '/main.php';
                }
            } else {
                $file = $this->getSeederDefaultFile();
            }
        }

        return $file;
    }

    public function getSeederFolder(IOInterface $io): string
    {
        if ($dir = $io->getOption('dir')) {
            return $dir;
        }

        return $this->app->path('@seeders');
    }
}
