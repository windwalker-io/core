<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

use Windwalker\Core\Repository\Repository;
use Windwalker\Core\Repository\Traits\DatabaseModelTrait;

/**
 * The AbstractExporter class.
 *
 * @since  2.1.1
 */
abstract class AbstractExporter extends Repository
{
    use DatabaseModelTrait;

    /**
     * export
     *
     * @param  string  $file
     *
     * @return void
     */
    abstract public function export(string $file);

    /**
     * getCreateTable
     *
     * @param string $table
     *
     * @return array|mixed|string
     */
    abstract protected function getCreateTable($table);

    /**
     * getInserts
     *
     * @param string $table
     *
     * @return mixed|null|string
     */
    abstract protected function getInserts($table);
}
