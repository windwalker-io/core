<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use Psr\Http\Message\StreamInterface;

/**
 * The Exporter class.
 *
 * @since  3.0
 */
class PostgresqlExporter extends AbstractExporter
{
    protected function doExport(StreamInterface $stream, array $options = []): void
    {
        echo 'Postgresql exporter not yet prepared.';
    }

    protected function getCreateTable(string $table): string
    {
        return '';
    }

    protected function getInserts(string $table): string
    {
        return '';
    }
}
