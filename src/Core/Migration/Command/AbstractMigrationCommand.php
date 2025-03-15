<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use DomainException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\Command\CommandDatabaseTrait;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Driver\Pdo\DsnHelper;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Environment\Environment;

/**
 * The AbstractMigrationCommand class.
 */
abstract class AbstractMigrationCommand implements CommandInterface
{
    use CommandDatabaseTrait;

    public const AUTO_BACKUP = 1;

    public const NO_TIME_LIMIT = 1 << 1;

    public const CREATE_DATABASE = 1 << 2;

    public const TOGGLE_CONNECTION = 1 << 3;

    #[Inject]
    protected ?ApplicationInterface $app = null;

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'The migration files directory.'
        );

        // $command->addOption(
        //     'package',
        //     'p',
        //     InputOption::VALUE_REQUIRED,
        //     'The target package migrations.'
        // );

        $this->configureDatabaseOptions($command);

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force run.'
        );
    }

    protected function preprocess(IOInterface $io, int $options = 0): void
    {
        if (!class_exists(DatabaseAdapter::class)) {
            throw new DomainException('Please install windwalker/database first.');
        }

        if ($options & static::NO_TIME_LIMIT) {
            set_time_limit(0);
        }

        if ($options & static::TOGGLE_CONNECTION && $conn = $io->getOption('connection')) {
            $container = $this->app->getContainer();
            $container->getParameters()
                ->setDeep('database.default', $conn);

            $this->databaseManager->cacheReset();
        }

        if ($options & static::CREATE_DATABASE) {
            $this->createDatabase($io);
        }

        if ($options & static::AUTO_BACKUP) {
            $this->backup($io);
        }
    }

    /**
     * Create database if not exists before migration command start.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    protected function createDatabase(IOInterface $io): void
    {
        if ($io->getOption('no-create-db') !== false) {
            return;
        }

        $conn = $io->getOption('connection');
        $factory = $this->databaseManager->getDatabaseFactory();

        $db = $this->databaseManager->get($conn);
        $db->disconnect();

        $options = DatabaseManager::mergeDsnToOptions($db->getOptions());

        $dbname = $options['dbname'];

        $options['dbname'] = null;

        $dbPreset = $factory->create(
            $options['driver'],
            $options
        );

        $schema = $dbPreset->getSchemaManager($dbname);

        if (!$schema->exists()) {
            $schema->create();
            $io->writeln('');
            $io->writeln("Database (Schema) <info>{$dbname}</info> auto created.");
        }

        $dbPreset->disconnect();
    }

    public function getDatabaseConnection(IOInterface $io): DatabaseAdapter
    {
        return $this->databaseManager->get($io->getOption('connection'));
    }

    /**
     * The system default migration folder.
     *
     * @return  string
     */
    public function getDefaultMigrationFolder(): string
    {
        return $this->app->config('@migrations');
    }

    /**
     * Get migration folder from options or return default.
     *
     * @param  IOInterface  $io
     *
     * @return  string
     */
    public function getMigrationFolder(IOInterface $io): string
    {
        $dir = $io->getOption('dir');

        // todo: package dir

        return $dir ?: $this->getDefaultMigrationFolder();
    }

    /**
     * Get log file from options or return default.
     *
     * @param  IOInterface  $io
     *
     * @return  string|null
     */
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

    /**
     * Do auto backup if allowed.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     *
     * @throws Exception
     */
    protected function backup(IOInterface $io): void
    {
        if ($io->getOption('no-backup') === false) {
            $io->writeln('');
            $io->writeln('<fg=gray>Backing up SQL...</>');

            $compress = env('DB_BACKUP_COMPRESS');
            $options = [
                'compress' => $compress
            ];

            $dbExportService = $this->app->make(DatabaseExportService::class);

            try {
                $dest = $dbExportService->export($io, $options);

                $io->writeln('SQL backup to: <info>' . $dest->getRealPath() . '</info>');
                $io->style()->newLine();
            } catch (DomainException $e) {
                $io->errorStyle()->warning($e->getMessage());
            }
        }
    }

    /**
     * Check APP_ENV is dev or stop.
     *
     * @param  IOInterface  $io
     *
     * @return  bool
     */
    protected function confirm(IOInterface $io): bool
    {
        if ($io->getOption('force')) {
            return true;
        }

        $confirm = $io->ask(
            new ConfirmationQuestion(
                'Do you really want to run migration/seeding?: [N/y] ',
                false
            )
        );

        if (!$confirm) {
            $io->writeln('User canceled.');
        }

        return $confirm;
    }

    /**
     * Get the ENV suggestion depend on platform.
     *
     * @param  string  $env
     * @param  string  $value
     *
     * @return  string
     *
     * @since  3.5.3
     */
    public function getEnvCmd(string $env = 'APP_ENV', string $value = 'dev'): string
    {
        $prefix = Environment::isWindows()
            ? 'set'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
