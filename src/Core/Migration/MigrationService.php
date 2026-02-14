<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use RuntimeException;
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
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Stream\Stream;

use function Windwalker\chronos;

use function Windwalker\depth;

use const Windwalker\Stream\WRITE_ONLY_FROM_END;

/**
 * The MigrationService class.
 */
class MigrationService implements EventAwareInterface
{
    use MessageOutputTrait;

    protected array $ignoreVersions = [];

    public bool $ignoreErrors = false;

    /**
     * MigrationService constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  DatabaseAdapter|null  $db
     */
    public function __construct(protected ApplicationInterface $app, protected ?DatabaseAdapter $db = null)
    {
        // $this->db?->addEventDealer($this->getEventDispatcher());
    }

    /**
     * @param  string  $path
     * @param  string|null  $targetVersion
     * @param  string|null  $logFile
     *
     * @return  int
     * @throws Exception
     */
    public function migrate(string $path, ?string $targetVersion, ?string $logFile = null): int
    {
        $migrations = $this->getMigrations($path);
        $versions = $this->getVersions();
        $currentVersion = $this->getCurrentVersion();

        if ($migrations === []) {
            throw new RuntimeException('No migrations found.');
        }

        if ($targetVersion === null) {
            $targetVersion = max(array_merge($versions, array_keys($migrations)));
        } elseif ($targetVersion !== '0' && empty($migrations[$targetVersion])) {
            throw new RuntimeException('Version is not valid.');
        }

        if ($logFile) {
            $this->handleLogging($logFile);
        }

        $count = 0;
        $direction = ($targetVersion >= $currentVersion) ? MigrationDirection::UP : MigrationDirection::DOWN;

        if ($direction === MigrationDirection::DOWN) {
            krsort($migrations);

            foreach ($migrations as $migration) {
                if ($migration->version <= $targetVersion) {
                    break;
                }

                if (in_array((string) $migration->version, $versions, true)) {
                    $this->executeMigration($migration, MigrationDirection::DOWN);
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
                    $this->executeMigration($migration, MigrationDirection::UP);
                }

                $count++;
            }
        }

        if (!$count) {
            $this->emitMessage('No changes.');
        }

        return $count;
    }

    public function executeMigration(
        AbstractMigration $migration,
        MigrationDirection $direction = MigrationDirection::UP
    ): void {
        $start = chronos();

        // Ignore if squashing
        if (in_array($migration->version, $this->ignoreVersions, true)) {
            $this->emitMessage(
                sprintf(
                    '<fg=gray>%s</> <info>%s</info> <fg=%s>%s</>... ',
                    $migration->version,
                    $migration->name,
                    'gray',
                    'IGNORE'
                ),
                true
            );
            $this->endMigration($migration, $direction, $start);

            return;
        }

        $handler = $migration->get($direction);

        if (!$handler) {
            return;
        }

        $this->emitMessage(
            sprintf(
                '<fg=gray>%s</> <info>%s</info> <fg=%s>%s</>... ',
                $migration->version,
                $migration->name,
                $direction === MigrationDirection::UP
                    ? 'bright-cyan'
                    : 'magenta',
                strtoupper($direction->name)
            ),
            false
        );

        $this->app->getContainer()->getAttributesResolver()->resolveObjectMembers($migration);
        $migration->addEventDealer($this);

        try {
            $this->app->call($handler);

            $this->emitMessage('<fg=bright-green>Success</>', true);
        } catch (\Throwable $e) {
            $this->emitMessage('<fg=bright-red>Failed</>', true);

            if (!$this->ignoreErrors) {
                throw $e;
            }

            $this->displayIgnoreErrorMessages($e, $migration);
        }

        // $this['log.' . $versionInfo['id']] = [
        //     'id' => $versionInfo['id'],
        //     'direction' => $direction,
        //     'name' => $versionInfo['name'],
        // ];

        $this->endMigration($migration, $direction, $start);
    }

    public function endMigration(
        AbstractMigration $migration,
        MigrationDirection $direction,
        DateTimeImmutable $start
    ): void {
        $end = chronos();

        $this->storeVersion($migration, $direction, $start, $end);

        // Reset Tables
        $this->db->getSchemaManager()->cacheReset();
    }

    /**
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

            $mig = new Migration();
            $db = $this->db;
            $orm = $db->orm();
            $app = $this->app;

            $mig = static::processIncluded(include $file->getPathname(), $mig)
                ->init($file, $this->db);

            $migrations[$mig->version] = $mig;
        }

        return $migrations;
    }

    protected static function processIncluded(mixed $included, Migration $default): AbstractMigration
    {
        if ($included instanceof AbstractMigration) {
            return $included;
        }

        return $default;
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
        AbstractMigration $migration,
        MigrationDirection $direction,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): void {
        if ($direction === MigrationDirection::UP) {
            $data['version'] = $migration->version;
            $data['name'] = $migration->name;
            $data['start_time'] = $start->format($this->db->getDateFormat());
            $data['end_time'] = $end->format($this->db->getDateFormat());

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
        $table = $this->db->getTableManager($this->getLogTable());

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

    public function copyMigrationFile(string $dir, string $name, string $source, array $data = []): FileCollection
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

        $format = $data['version_format'] ?? 'YmdHi%04d';
        $date = new DateTimeImmutable('now');
        $entity = $data['entity'] ?? 'Table';
        $version = static::generateVersion($date, $versions, $format);

        $year = $date->format('Y');

        return $codeGenerator->from($source)
            ->replaceTo(
                $dir,
                compact('name', 'version', 'year', 'entity'),
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

        $logStream = new Stream($logFile, WRITE_ONLY_FROM_END);

        // Log query
        $this->db->on(
            QueryEndEvent::class,
            function (QueryEndEvent $event) use ($logStream) {
                $logStream->write($event->debugQueryString . ";\n\n");
            }
        );

        // Log failed
        $this->db->on(
            QueryFailedEvent::class,
            function (QueryFailedEvent $event) use ($logStream) {
                $e = $event->exception;
                $logStream->write("-- ERROR: {$e->getMessage()}\n{$event->debugQueryString}\n\n");
            }
        );
    }

    public static function generateVersion(
        DateTimeInterface|string $date,
        array $versions = [],
        string $format = 'YmdHi%04d'
    ): string {
        $date = chronos($date);

        $i = 1;

        do {
            $dateFormat = sprintf($format, $i);
            $version = $date->format($dateFormat);
            $i++;
        } while (in_array($version, $versions, true));

        return $version;
    }

    public function ignores(array $ignoreVersions): void
    {
        $this->ignoreVersions = $ignoreVersions;
    }

    public function squashIfNotFresh(array $ignoreVersions): void
    {
        // Check is fresh or not
        $currentVersion = $this->getCurrentVersion();

        if ($currentVersion !== '0') {
            // Let's clear log tables
            $db = $this->app->retrieve(DatabaseAdapter::class);
            $db->getTableManager($this->getLogTable())
                ->truncate();

            $this->ignores($ignoreVersions);
        }
    }

    protected function displayIgnoreErrorMessages(\Throwable $e, AbstractMigration $migration): void
    {
        $this->emitMessage(
            sprintf(
                '[IGNORED]: <comment>%s</comment>: %s (%s:%s)',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ),
            true
        );

        $traces = $e->getTrace();

        foreach ($traces as $trace) {
            if (Path::normalize($trace['file']) === Path::normalize($migration->file->getPathname())) {
                $this->emitMessage(
                    "  May caused at <comment>{$trace['file']}:{$trace['line']}</comment>",
                );
                break;
            }
        }
    }
}
