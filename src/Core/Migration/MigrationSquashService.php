<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Service;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\StrInflector;
use Windwalker\Utilities\StrNormalize;

use function Windwalker\fs;

#[Service]
class MigrationSquashService
{
    use MessageOutputTrait;

    public function __construct(protected ApplicationInterface $app, protected MigrationService $migrationService)
    {
    }

    public function squash(
        DatabaseAdapter $db,
        string $path,
        bool $group = true,
        bool $one = false,
    ) {
        $this->moveOldMigrations($path);

        $this->writeMigrationFiles(
            $path,
            $this->generateMigrationCodes(db: $db, one: $one, group: $group),
            $one
        );
    }

    /**
     * @param  string  $path
     *
     * @return  void
     */
    public function moveOldMigrations(string $path): void
    {
        $migrations = $this->migrationService->getMigrations($path);

        $bakFolder = fs($path . '/bak');
        $bakFolder->mkdir();

        foreach ($migrations as $migration) {
            Filesystem::move(
                $migration->file->getPathname(),
                $bakFolder->getPathname() . '/' . $migration->file->getBasename()
            );

            $this->emitMessage('[<fg=cyan>MOVE</>] ' . $migration->file->getPathname() . ' => ./bak');
        }
    }

    /**
     * @param  DatabaseAdapter  $db
     * @param  bool             $one
     * @param  bool             $group
     *
     * @return  array<string, array<string[]>>
     */
    public function generateMigrationCodes(DatabaseAdapter $db, bool $one, bool $group): array
    {
        $tables = $db->getSchema()->getTables();

        $migrateCodes = [];

        foreach ($tables as $table) {
            // Do not handle `migration_log` table.
            if ($table->tableName === $this->migrationService->getLogTable()) {
                continue;
            }

            $tableManager = $db->getTable($table->tableName);
            $builder = $this->app->make(
                MigrationSquashBuilder::class,
                [
                    DatabaseAdapter::class => $db,
                ]
            );

            [$up, $down] = $builder->build($tableManager);

            if ($one) {
                $migrateCodes['main'][] = [$up, $down];
            } elseif ($group) {
                $groupName = static::getGroupName($table->tableName);

                $migrateCodes[$groupName][] = [$up, $down];
            } else {
                $migrateCodes[$table->tableName][] = [$up, $down];
            }
        }

        return $migrateCodes;
    }

    /**
     * @param  string                          $path
     * @param  array<string, array<string[]>>  $migrateCodes
     * @param  bool                            $one
     *
     * @return  void
     */
    protected function writeMigrationFiles(string $path, array $migrateCodes, bool $one = false): void
    {
        $versions = $this->migrationService->getVersions();

        $squashVersion = MigrationService::generateVersion('now', $versions);
        $versions[] = $squashVersion;
        $newVersions = [];

        foreach ($migrateCodes as $groupName => $codes) {
            $version = MigrationService::generateVersion('now', $versions);
            $newVersions[] = $versions[] = $version;

            $migrateCodes[$groupName] = [$codes, $version];
        }

        if (!$one) {
            $squashMigName = 'SquashVersions';
            $migCode = MigrationSquashBuilder::buildSquashMigrationCode($squashMigName, $squashVersion, $newVersions);

            $this->writeAndLog(
                $this->toFileObject($path, $squashVersion, $squashMigName),
                $migCode,
            );
        }

        /** @var string $version */
        foreach ($migrateCodes as $groupName => [$codes, $version]) {
            $name = static::buildMigrationName($groupName);

            $ups = array_column($codes, 0);
            $downs = array_column($codes, 1);

            if ($one) {
                $squashCode = MigrationSquashBuilder::buildSquashActionCode($versions);

                array_unshift($ups, $squashCode);
            }

            $upCode = implode("\n\n", $ups);
            $downCode = implode("\n", $downs);

            $migCode = MigrationSquashBuilder::buildMigrationTemplate(
                $name,
                $upCode,
                $downCode,
                $version
            );

            $this->writeAndLog(
                $this->toFileObject($path, $version, $name),
                $migCode,
            );
        }
    }

    /**
     * @param  string  $tableName
     *
     * @return  string
     */
    protected static function buildMigrationName(string $tableName): string
    {
        $name = StrInflector::toSingular($tableName);

        return StrNormalize::toPascalCase($name) . 'Init';
    }

    protected static function getGroupName(string $tableName): string
    {
        [$name] = explode('_', $tableName, 2);

        return StrInflector::toSingular(strtolower($name));
    }

    public function writeAndLog(
        FileObject $file,
        string $code
    ): FileObject {
        $this->emitMessage('[<info>CREATE</info>] ' . $file->getPathname());

        return $file->write($code);
    }

    protected function toFileObject(string $path, string $version, string $name): FileObject
    {
        $name = $version . '_' . $name . '.php';

        return fs($path . '/' . $name);
    }
}
