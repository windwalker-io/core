<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Core\Console\CommandWrapper;
use Windwalker\Core\Console\IOInterface;

/**
 * The ResetCommand class.
 */
#[CommandWrapper('Reset migration versions.')]
class ResetCommand extends AbstractMigrationCommand
{
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

        $command->addOption(
            'seed',
            's',
            InputOption::VALUE_OPTIONAL,
            'Also import seeds.'
        );
        $command->addOption(
            'log',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Log queries, can provide a custom file.',
            false
        );
        $command->addOption(
            'no-backup',
            null,
            InputOption::VALUE_REQUIRED,
            'Do not backup database.'
        );
        $command->addOption(
            'no-create-db',
            null,
            InputOption::VALUE_REQUIRED,
            'Do not auto create database or schema.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     * @throws \Exception
     */
    public function execute(IOInterface $io)
    {
        // Dev check
        if (!$this->checkEnv($io)) {
            return 255;
        }

        $this->preprocessMigrations($io);
        $this->backup($io);

        $style = $io->style();
        $style->title('Rollback to 0 version...');

        $args                   = $io->getInput()->getArguments();
        $args['version']        = '0';
        $args['--no-backup']    = '1';
        $args['--no-create-db'] = '1';

        $this->app->runCommand('mig:go', $io->extract($args));

        $style->title('Migrating to latest version...');

        unset($args['version']);

        $this->app->runCommand('mig:go', $io->extract($args));

        return 0;
    }
}
