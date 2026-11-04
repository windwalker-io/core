<?php

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Exception;
use SplFileInfo;
use Throwable;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\StrNormalize;

use function Windwalker\tid;

use const Windwalker\Stream\WRITE_ONLY_RESET;

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
    ) {}

    /**
     * export
     *
     * @param  OutputInterface|null  $output
     *
     * @return  FileObject
     *
     * @throws  Exception
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

        $this->rotate($dest->getPath(), $options['keep'] ?? null);

        $exporter = $this->exporterFactory->createExporter($this->db, $this->app);

        $exporter->on(MessageOutputEvent::class, fn(MessageOutputEvent $event) => $event->writeWith($output ?? $this->app));

        if ($compress) {
            $dest = $dest->appendPath('.gz');
        }

        $stream = $dest->getStream(WRITE_ONLY_RESET);

        try {
            $exporter->exportToPsrStream($stream, $options);
            $stream->close();

            if ($exporter->getFileExtension() !== 'sql') {
                $extension = $exporter->getFileExtension() . ($compress ? '.gz' : '');
                $path = $dest->getPathname();
                $suffix = $compress ? '.sql.gz' : '.sql';
                $path = str_ends_with($path, $suffix)
                    ? substr($path, 0, -strlen($suffix)) . '.' . $extension
                    : $path . '.' . $extension;
                $dest = $dest->moveTo($path);
            }
        } catch (Throwable $e) {
            $stream->close();
            Filesystem::delete($dest->getPathname());

            throw $e;
        } finally {
            $stream->close();
        }

        clearstatcache(true, $dest->getPathname());

        return $dest;
    }

    /**
     * @param  string    $dir
     * @param  int|null  $max
     *
     * @since  3.4.2
     */
    protected function rotate(string $dir, ?int $max = null): void
    {
        $files = Filesystem::files($dir)->toArray();

        rsort($files);

        array_splice($files, 0, ($max ?? ($this->app->config('database.backup.max') ?? 10)) - 1);

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
    public static function stripPrefix(string $table, ?string $prefix = null): string
    {
        $prefix = (string) $prefix;

        $num = strlen($prefix);

        if (str_starts_with($table, $prefix)) {
            $table = '#__' . substr($table, $num);
        }

        return $table;
    }
}
