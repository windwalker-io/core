<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
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
     * @param  array            $options
     *
     * @return  void
     */
    public function exportToPsrStream(StreamInterface $stream, array $options = []): void;

    /**
     * Export to stream resource.
     *
     * @param  resource  $resource
     * @param  array     $options
     *
     * @return  void
     */
    public function exportToStream($resource, array $options = []): void;

    /**
     * Export to SQL string.
     *
     * @param  array  $options  *
     *
     * @return  string
     */
    public function exportToSQLString(array $options = []): string;
}
