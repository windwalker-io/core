<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\IOInterface;
use Windwalker\DI\Attributes\Inject;

/**
 * Trait MigrationTrait
 */
trait MigrationTrait
{
    #[Inject]
    protected ?ApplicationInterface $app = null;

    public function getDefaultMigrationFolder(): string
    {
        return $this->app->config('@migrations');
    }

    public function getMigrationFolder(IOInterface $io): string
    {
        $dir = $io->getOption('dir');

        // todo: package dir

        return $dir ?: $this->getDefaultMigrationFolder();
    }

    protected function getLogFile(IOInterface $io): ?string
    {
        $log = $io->getOption('log');

        if ($log === false) {
            return null;
        }

        if ($log === null) {
            $log = $this->app->config('@temp') . '/db/last-migration-logs.sql';
        }
        
        return $log;
    }
}
