<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\DI\Attributes\Service;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\StrInflector;
use Windwalker\Utilities\StrNormalize;

use function Windwalker\fs;

#[Service]
class MigrationSquashService
{
    use MessageOutputTrait;

    protected DatabaseAdapter $db;

    public function __construct(protected ApplicationInterface $app, protected MigrationService $migrationService)
    {
    }

    public function squash(DatabaseAdapter $db, string $path)
    {
        $this->db = $db;

        $oldVersions = $versions = $this->migrationService->getVersions();
        $migrations = $this->migrationService->getMigrations($path);

        $tables = $db->getSchema()->getTables();

        foreach ($tables as $table) {
            $tableManager = $db->getTable($table->tableName);
            $builder = $this->app->make(
                MigrationSquashBuilder::class,
                [
                    DatabaseAdapter::class => $db,
                    TableManager::class => $tableManager,
                ]
            );

            $version = MigrationService::generateVersion('now', $versions);
            $versions[] = $version;
            $name = StrInflector::toSingular($tableManager->getName());
            $name = StrNormalize::toPascalCase($name) . 'Init';

            $fileName = $version . '_' . $name . '.php';

            $migCode = $builder->process(
                compact(
                    'version',
                    'name'
                )
            );

            $file = fs($path . '/' . $fileName);
            $file->write($migCode);

            $this->emitMessage('[<info>CREATE</info>] ' . $file->getPathname());
        }

        $db->getTable($this->migrationService->getLogTable())->truncate();

        foreach ($migrations as $migration) {
            Filesystem::deleteIfExists($migration->file->getPathname());

            $this->emitMessage('[<fg=cyan>DELETE</>] ' . $migration->file->getPathname());
        }
    }
}
