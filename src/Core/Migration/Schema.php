<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Migration;

use Windwalker\Database\Command\AbstractTable;
use Windwalker\Database\Schema\Column;
use Windwalker\Database\Schema\Key;

/**
 * The Schema class.
 *
 * @since  {DEPLOY_VERSION}
 */
class Schema
{
	/**
	 * Property table.
	 *
	 * @var  AbstractTable
	 */
	protected $table;

	/**
	 * Schema constructor.
	 *
	 * @param AbstractTable $table
	 */
	public function __construct(AbstractTable $table)
	{
		$this->table = $table;
	}

	/**
	 * addColumn
	 *
	 * @param string $name
	 * @param Column $column
	 *
	 * @return  Column
	 */
	public function addColumn($name, Column $column)
	{
		$column->name($name);

		$this->table->addColumn($column);

		return $column;
	}

	/**
	 * addIndex
	 *
	 * @param string $type
	 * @param string $name
	 * @param array  $columns
	 * @param string $comment
	 * @param array  $options
	 *
	 * @return  static
	 */
	public function addIndex($type, $name = null, $columns = array(), $comment = null, $options = array())
	{
		$this->table->addIndex($type, $name, $columns, $comment, $options);

		return $this;
	}

	/**
	 * Method to get property Table
	 *
	 * @return  AbstractTable
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Method to set property table
	 *
	 * @param   AbstractTable $table
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setTable($table)
	{
		$this->table = $table;

		return $this;
	}
}
