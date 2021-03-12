<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

/**
 * The Exporter class.
 *
 * @since  3.0
 */
class PostgresqlExporter extends AbstractExporter
{
    /**
     * export
     *
     * @param  string  $file
     *
     * @return mixed|string
     */
    public function doExport(string $file)
    {
        echo 'Postgresql exporter not yet prepared.';

        return '';
    }

    /**
     * getCreateTable
     *
     * @param $table
     *
     * @return array|mixed|string
     */
    protected function getCreateTable($table)
    {
    }

    /**
     * getInserts
     *
     * @param $table
     *
     * @return mixed|null|string
     */
    protected function getInserts($table)
    {
    }
}
