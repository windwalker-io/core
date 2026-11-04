<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Stream\GzStream;
use Windwalker\Stream\Stream;

use const Windwalker\Stream\READ_WRITE_FROM_BEGIN;
use const Windwalker\Stream\WRITE_ONLY_RESET;

/**
 * The AbstractExporter class.
 *
 * @since  2.1.1
 */
abstract class AbstractExporter implements ExporterInterface
{
    use MessageOutputTrait;

    /**
     * AbstractExporter constructor.
     *
     * @param  DatabaseAdapter       $db
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected DatabaseAdapter $db, protected ApplicationInterface $app) {}

    /**
     * Export to PSR7 stream.
     *
     * @param  StreamInterface  $stream
     * @param  array            $options
     *
     * @return  void
     */
    public function exportToPsrStream(StreamInterface $stream, array $options = []): void
    {
        $this->export($stream, $options);
    }

    /**
     * Export to stream resource.
     *
     * @param  resource  $resource
     * @param  array     $options
     *
     * @return  void
     */
    public function exportToStream($resource, array $options = []): void
    {
        $this->export(new Stream($resource), $options);
    }

    /**
     * Export to SQL string.
     *
     * @param  array  $options  *
     *
     * @return  string
     */
    public function exportToSQLString(array $options = []): string
    {
        $this->export(
            $stream = new Stream('php://memory', READ_WRITE_FROM_BEGIN),
            $options
        );

        return $stream->getContents();
    }

    protected function export(StreamInterface $stream, array $options = []): void
    {
        $this->doExport($stream, $options);
    }

    public function getFileExtension(): string
    {
        return 'sql';
    }

    protected function createGzipStream(StreamInterface $stream, ?string $uri = null): StreamInterface
    {
        $uri ??= $stream->getMetadata('uri');

        if (!is_string($uri) || $uri === '') {
            throw new RuntimeException('Unable to open the gzip backup destination.');
        }

        $stream->close();

        return GzStream::fromFilePath($uri, WRITE_ONLY_RESET);
    }

    /**
     * export
     *
     * @param  StreamInterface  $stream
     * @param  array            $options
     *
     * @return  void
     */
    abstract protected function doExport(StreamInterface $stream, array $options = []): void;

    /**
     * getCreateTable
     *
     * @param  string  $table
     *
     * @return  array|mixed|string
     */
    abstract protected function getCreateTable(string $table): string;

    /**
     * getInserts
     *
     * @param  string  $table
     *
     * @return  mixed|null|string
     */
    abstract protected function getInserts(string $table): string;
}
