<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use Psr\Http\Message\StreamInterface;

/**
 * The ExporterInterface class.
 */
interface ExporterInterface
{
    /**
     * Export to PSR7 stream.
     *
     * @param  StreamInterface  $stream
     *
     * @return  void
     */
    public function exportToPsrStream(StreamInterface $stream): void;

    /**
     * Export to stream resource.
     *
     * @param resource $resource
     *
     * @return  void
     */
    public function exportToStream($resource): void;

    /**
     * Export to SQL string.
     *
     * @return  string
     */
    public function exportToSQLString(): string;
}
