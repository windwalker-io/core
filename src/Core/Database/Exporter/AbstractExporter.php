<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

use Windwalker\Core\Model\ModelRepository;
use Windwalker\Core\Model\Traits\DatabaseModelTrait;

/**
 * The AbstractExporter class.
 *
 * @since  2.1.1
 */
abstract class AbstractExporter extends ModelRepository
{
	use DatabaseModelTrait;

	/**
	 * export
	 *
	 * @return mixed|string
	 */
	abstract public function export();

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
