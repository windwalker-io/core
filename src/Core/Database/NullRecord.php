<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Database;

use Windwalker\Database\DatabaseFactory;
use Windwalker\DataMapper\DataMapper;
use Windwalker\Record\Record;

/**
 * The NullRecord class.
 *
 * @since  3.0.1
 */
class NullRecord extends Record
{
	/**
	 * Name of the primary key fields in the table.
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $keys = null;

	/**
	 * Property fields.
	 *
	 * @var  array
	 */
	protected $fields = array();

	/**
	 * Object constructor to set table and key fields.  In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param   string      $table   Name of the table to model.
	 * @param   mixed       $keys    Name of the primary key field in the table or array of field names that
	 *                               compose the primary key.
	 * @param   DataMapper  $mapper  DatabaseDriver object.
	 *
	 * @since   2.0
	 */
	public function __construct($table = null, $keys = null, $mapper = null)
	{
		$this->data = [];
		$this->mapper = new NullDataMapper;
		$this->keys = $keys ? : ['id'];
		$this->db = DatabaseFactory::getDbo();
	}
}
