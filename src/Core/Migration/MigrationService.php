<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\Core\Generator\FileCollection;
use Windwalker\Core\Migration\Exception\MigrationExistsException;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Event\QueryEndEvent;
use Windwalker\Database\Event\QueryFailedEvent;
use Windwalker\Database\Schema\Schema;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Stream\Stream;
use Windwalker\Utilities\SimpleTemplate;

use function Windwalker\chronos;

/**
 * The MigrationService class.
 */
class MigrationService implements EventAwareInterface
{
    use MessageOutputTrait;

    /**
     * MigrationService constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  DatabaseAdapter|null  $db
     */
    public function __construct(protected ApplicationInterface $app, protected ?DatabaseAdapter $db = null)
    {
        $this->db?->addEventDealer($this->getEventDispatcher());
    }

    /**
     * migrate
     *
     * @param  string       $path
     * @param  string|null  $targetVersion
     * @param  string|null  $logFile
     *
     * @return  int
     */
    public function migrate(string $path, ?string $targetVersion, ?string $logFile = null): int
    {
        $migrations     = $this->getMigrations($path);
        $versions       = $this->getVersions();
        $currentVersion = $this->getCurrentVersion();

        if ($migrations === []) {
            throw new \RuntimeException('No migrations found.');
        }

        if ($targetVersion === null) {
            $targetVersion = max(array_merge($versions, array_keys($migrations)));
        } elseif ($targetVersion !== '0' && empty($migrations[$targetVersion])) {
            throw new \RuntimeException('Version is not valid.');
        }

        if ($logFile) {
            $this->handleLogging($logFile);
        }

        $count     = 0;
        $direction = ($targetVersion >= $currentVersion) ? Migration::UP : Migration::DOWN;

        if ($direction === Migration::DOWN) {
            krsort($migrations);

            foreach ($migrations as $migration) {
                if ($migration->version <= $targetVersion) {
                    break;
                }

                if (in_array((string) $migration->version, $versions, true)) {
                    $this->executeMigration($migration, Migration::DOWN);
                }

                $count++;
            }
        } else {
            ksort($migrations);

            foreach ($migrations as $migration) {
                if ($migration->version > $targetVersion) {
                    break;
                }

                if (!in_array((string) $migration->version, $versions, true)) {
                    $this->executeMigration($migration, Migration::UP);
                }

                $count++;
            }
        }

        if (!$count) {
            $this->emitMessage('No changes.');
        }

        return $count;
    }

    /**
     * executeMigration
     *
     * @param  Migration  $migration
     * @param  string     $direction
     *
     * @return  void
     *
     * @throws \Exception
     */
    public function executeMigration(Migration $migration, string $direction = Migration::UP): void
    {
        $mig = $migration;
        $db = $this->db;
        $orm = $db->orm();
        $app = $this->app;

        include $migration->file->getPathname();

        $handler = $migration->get($direction);

        if (!$handler) {
            return;
        }

        $start = chronos();

        $this->emitMessage(
            sprintf(
                '<fg=gray>%s</> <info>%s</info> <fg=%s>%s</>... ',
                $migration->version,
                $migration->name,
                $direction === Migration::UP
                    ? 'bright-cyan'
                    : 'magenta',
                strtoupper($direction)
            ),
            false
        );

        $migration->addEventDealer($this);

        $this->app->call($handler);

        $this->emitMessage('<fg=bright-green>Success</>', true);

        $end = chronos();

        // $this['log.' . $versionInfo['id']] = [
        //     'id' => $versionInfo['id'],
        //     'direction' => $direction,
        //     'name' => $versionInfo['name'],
        // ];

        $this->storeVersion($migration, $direction, $start, $end);
    }

    /**
     * getMigrations
     *
     * @param  string  $path
     *
     * @return  array<string, Migration>
     */
    public function getMigrations(string $path): array
    {
        $files = Filesystem::files($path);

        $migrations = [];

        foreach ($files as $file) {
            $ext = $file->getExtension();

            if ($ext !== 'php') {
                continue;
            }

            $mig = new Migration($file, $this->db);

            $migrations[$mig->version] = $mig;
        }

        return $migrations;
    }

    /**
     * getVersions
     *
     * @return  array
     */
    public function getVersions(): array
    {
        $this->initLogTable();

        return $this->db->select('version')
            ->from($this->getLogTable())
            ->order('version', 'ASC')
            ->loadColumn()
            ->dump();
    }

    /**
     * getCurrentVersion
     *
     * @return  string
     */
    public function getCurrentVersion(): string
    {
        $versions = $this->getVersions();

        return (string) ($versions[array_key_last($versions)] ?? '0');
    }

    public function storeVersion(
        Migration $migration,
        string $direction,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): void {
        if ($direction === Migration::UP) {
            $data['version']    = $migration->version;
            $data['name']       = $migration->name;
            $data['start_time'] = $start->format($this->db->getDateFormat());
            $data['end_time']   = $end->format($this->db->getDateFormat());

            $this->db->getWriter()->insertOne($this->getLogTable(), $data);
        } else {
            $this->db->delete($this->getLogTable())
                ->where('version', $migration->version)
                ->execute();
        }
    }

    /**
     * initLogTable
     *
     * @return  void
     */
    public function initLogTable(): void
    {
        $table = $this->db->getTable($this->getLogTable());

        if ($table->exists()) {
            if (!$table->hasColumn('name')) {
                $table->update(
                    function (Schema $schema) {
                        $schema->varchar('name');
                    }
                );
            }

            return;
        }

        $table->create(
            function (Schema $schema) {
                $schema->varchar('version');
                $schema->varchar('name');

                // Use exists time as timestamp before framework supports CURRENT_TIMESTAMP
                $schema->timestamp('start_time')->defaultCurrent();
                $schema->timestamp('end_time')->defaultCurrent();

                $schema->addPrimaryKey(['version', 'name']);
            }
        );
    }

    public function getLogTable(): string
    {
        return $this->app->config('db.migration.table_name') ?: 'migration_log';
    }

    /**
     * copyMigrationFile
     *
     * @param  string  $dir
     * @param  string  $name
     * @param  string  $source
     *
     * @return  FileCollection
     */
    public function copyMigrationFile(string $dir, string $name, string $source, array $options = []): FileCollection
    {
        $codeGenerator = $this->app->make(CodeGenerator::class);
        $migrations = $this->getMigrations($dir);
        $versions = [];

        // Check name not exists
        foreach ($migrations as $migration) {
            if (strtolower($name) === strtolower($migration->name)) {
                throw new MigrationExistsException(
                    $migration,
                    'Migration: <info>' . $name . "</info> has exists. \nFile at: <info>" .
                    $migration->file->getPathname() . '</info>'
                );
            }

            $versions[] = $migration->version;
        }

        $format = $options['version_format'] ?? 'YmdHi%04d';
        $i = 1;
        $date = new \DateTimeImmutable('now');

        do {
            $dateFormat = sprintf($format, $i);
            $version = $date->format($dateFormat);
            $i++;
        } while (in_array($version, $versions, true));

        $year    = $date->format('Y');

        return $codeGenerator->from($source)
            ->replaceTo(
                $dir,
                compact('name', 'version', 'year'),
            );
    }

    /**
     * handleLogging
     *
     * @param  string  $logFile
     *
     * @return  void
     *
     * @since  4.0
     */
    public function handleLogging(string $logFile): void
    {
        if (is_file($logFile)) {
            Filesystem::delete($logFile);
        }

        Filesystem::mkdir(dirname($logFile));

        $logStream = new Stream($logFile, Stream::MODE_WRITE_ONLY_FROM_END);

        // Log query
        $this->on(
            QueryEndEvent::class,
            function (QueryEndEvent $event) use ($logStream) {
                $logStream->write($event->getDebugQueryString() . "\n\n");
            }
        );

        // Log failed
        $this->on(
            QueryFailedEvent::class,
            function (QueryFailedEvent $event) use ($logStream) {
                $e = $event->getException();
                $logStream->write("-- ERROR: {$e->getMessage()}\n{$event->getDebugQueryString()}\n\n");
            }
        );
    }
}
