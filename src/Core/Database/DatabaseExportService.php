<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
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
     * @param  DatabaseAdapter       $db
     */
    public function __construct(
        #[Autowire]
        protected ExporterFactory $exporterFactory,
        protected ApplicationInterface $app,
        protected DatabaseAdapter $db
    ) {
    }

    /**
     * export
     *
     * @param  OutputInterface|null  $output
     *
     * @return FileObject
     *
     * @throws \Exception
     */
    public function export(?OutputInterface $output = null): FileObject
    {
        $dir  = $this->app->config('database.backup.dir') ?: '@temp/sql-backup';
        $dir  = $this->app->path($dir);
        $dest = sprintf(
            '%s/ww-sql-backup-%s-%s.sql',
            $dir,
            gmdate('Y-m-d-H-i-s'),
            uid()
        );

        return $this->exportTo($dest, $output);
    }

    public function exportTo(
        string|\SplFileInfo $dest,
        ?OutputInterface $output = null
    ): FileObject {
        $dest = FileObject::wrap($dest);
        $dest->getParent()->mkdir();

        $this->rotate($dest->getPath());

        $exporter = $this->exporterFactory->createExporter($this->db, $this->app);

        $exporter->on(MessageOutputEvent::class, fn(MessageOutputEvent $event) => $event->writeWith($output));

        $exporter->exportToPsrStream($dest->getStream(Stream::MODE_READ_WRITE_RESET));

        return $dest;
    }

    /**
     * rotate
     *
     * @param  string  $dir
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
        $num = strlen((string) $prefix);

        if (str_starts_with($table, $prefix)) {
            $table = '#__' . substr($table, $num);
        }

        return $table;
    }
}
