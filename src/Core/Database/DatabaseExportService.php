<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Exception;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Stream\GzStream;
use Windwalker\Stream\Stream;
use Windwalker\Utilities\StrNormalize;

use function Windwalker\tid;
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
     * @throws Exception
     */
    public function export(?OutputInterface $output = null, array $options = []): FileObject
    {
        $dir = $this->app->config('database.backup.dir') ?: '@temp/sql-backup';
        $dir = $this->app->path($dir);
        $appName = $this->app->config('app.name') ?? 'windwalker';
        $dest = sprintf(
            '%s/%s-backup-%s-%s.sql',
            $dir,
            StrNormalize::toKebabCase(Path::makeUtf8Safe($appName)),
            gmdate('Y-m-d-H-i-s'),
            tid()
        );

        return $this->exportTo($dest, $output, $options);
    }

    public function exportTo(
        string|SplFileInfo $dest,
        ?OutputInterface $output = null,
        array $options = [],
    ): FileObject {
        $compress = $options['compress'] ?? false;
        $dest = FileObject::wrap($dest);

        $dest->getParent()->mkdir();

        $this->rotate($dest->getPath());

        $exporter = $this->exporterFactory->createExporter($this->db, $this->app);

        $exporter->on(MessageOutputEvent::class, fn(MessageOutputEvent $event) => $event->writeWith($output));

        $streamClass = Stream::class;

        if ($compress) {
            $gzipCliExists = $this->checkGzipExists();
            $dest = $dest->appendPath('.gz');

            $options['gz'] = $gzipCliExists ? 'cli' : 'php';

            if ($options['gz'] === 'php') {
                $streamClass = GzStream::class;
            }
        }

        $exporter->exportToPsrStream(
            $dest->getStream(Stream::MODE_WRITE_ONLY_RESET, $streamClass),
            $options
        );

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

        array_splice($files, 0, ($this->app->config('database.backup.max') ?? 10) - 1);

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
        $prefix = (string) $prefix;

        $num = strlen($prefix);

        if (str_starts_with($table, $prefix)) {
            $table = '#__' . substr($table, $num);
        }

        return $table;
    }

    protected function checkGzipExists(): bool
    {
        $process = $this->app->runProcess('which gzip');

        return $process->getExitCode() === Command::SUCCESS;
    }
}
