<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Seed\SeedService;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Attributes\Service;
use Windwalker\Utilities\Str;

/**
 * The CreateCommand class.
 */
#[CommandWrapper(description: 'Create a seeder.')]
class SeedCreateCommand extends AbstractSeedCommand
{
    /**
     * CreateCommand constructor.
     *
     * @param  SeedService    $seedService
     */
    public function __construct(
        #[Service]
        protected SeedService $seedService
    ) {
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
        parent::configure($command);

        $command->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Migration name',
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    public function execute(IOInterface $io): int
    {
        $name = $io->getArgument('name');

        $this->seedService->copySeedFile(
            $this->getSeederFolder($io),
            $name,
            __DIR__ . '/../../../../resources/templates/seed/*'
        );

        return 0;
    }
}
