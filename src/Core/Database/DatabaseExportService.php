<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Database\DatabaseAdapter;

use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Folder;
use Windwalker\Stream\Stream;

use function Windwalker\uid;

/**
 * The DatabaseExportService class.
 */
class DatabaseExportService
{
    /**
     * DatabaseExportService constructor.
     *
     * @param  ExporterFactory       $exporterFactory
     * @param  ApplicationInterface  $app
     * @param  DatabaseManager       $databaseManager
     */
    public function __construct(
        #[Autowire]
        protected ExporterFactory $exporterFactory,
        protected ApplicationInterface $app,
        protected DatabaseManager $databaseManager
    ) {
    }

    /**
     * export
     *
     * @param  string|DatabaseAdapter|null  $db
     * @param  OutputInterface|null         $output
     *
     * @return  StreamInterface
     *
     * @throws \Exception
     */
    public function export(string|DatabaseAdapter|null $db = null, ?OutputInterface $output = null): StreamInterface
    {
        if ($output) {
            $output->writeln('Backing up SQL...');
        }

        $dir = $this->app->config('database.backup.dir') ?: '@temp/sql-backup';
        $dir = $this->app->path($dir);
        $dest = sprintf(
            '%s/ww-sql-backup-%s-%s.sql',
            $dir,
            gmdate('Y-m-d-H-i-s'),
            uid()
        );

        Filesystem::mkdir(dirname($dest));

        $this->rotate($dir);

        if (!$db instanceof DatabaseAdapter) {
            $db = $this->databaseManager->get($db);
        }

        $exporter = $this->exporterFactory->createExporter($db, $this->app);

        $exporter->setIO($output);

        $exporter->exportToPsrStream(
            $stream = new Stream($dest, Stream::MODE_READ_WRITE_RESET)
        );

        if ($output) {
            $output->writeln('SQL backup to: <info>' . $dest . '</info>');
        }

        return $stream;
    }

    /**
     * rotate
     *
     * @param string $dir
     *
     * @since  3.4.2
     */
    protected function rotate(string $dir): void
    {
        $files = Filesystem::files($dir)->toArray();

        rsort($files);

        array_splice($files, 0, $this->app->config('database.backup.max') ?? 20);

        foreach ($files as $file) {
            Filesystem::delete($file);
        }
    }

    /**
     * stripPrefix
     *
     * @param  string       $table
     * @param  string|null  $prefix
     *
     * @return  string
     */
    public static function stripPrefix(string $table, string $prefix = null): string
    {
        $num = strlen($prefix);

        if (str_starts_with($table, $prefix)) {
            $table = '#__' . substr($table, $num);
        }

        return $table;
    }
}
