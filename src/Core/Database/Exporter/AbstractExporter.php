<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

use Psr\Http\Message\StreamInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Event\MessageOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Stream\Stream;

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
    public function __construct(protected DatabaseAdapter $db, protected ApplicationInterface $app)
    {
    }

    /**
     * Export to PSR7 stream.
     *
     * @param  StreamInterface  $stream
     *
     * @return  void
     */
    public function exportToPsrStream(StreamInterface $stream): void
    {
        $this->export($stream);
    }

    /**
     * Export to stream resource.
     *
     * @param  resource  $resource
     *
     * @return  void
     */
    public function exportToStream($resource): void
    {
        $this->export(new Stream($resource));
    }

    /**
     * Export to SQL string.
     *
     * @return  string
     */
    public function exportToSQLString(): string
    {
        $this->export($stream = new Stream('php://memory', Stream::MODE_READ_WRITE_FROM_BEGIN));

        return $stream->getContents();
    }

    protected function export(StreamInterface $stream): void
    {
        $this->doExport($stream);
    }

    /**
     * export
     *
     * @param  StreamInterface  $stream
     *
     * @return void
     */
    abstract protected function doExport(StreamInterface $stream): void;

    /**
     * getCreateTable
     *
     * @param  string  $table
     *
     * @return array|mixed|string
     */
    abstract protected function getCreateTable(string $table): string;

    /**
     * getInserts
     *
     * @param  string  $table
     *
     * @return mixed|null|string
     */
    abstract protected function getInserts(string $table): string;
}
