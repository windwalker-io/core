<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Model;

use Windwalker\Core\Ioc;
use Windwalker\Database\Driver\DatabaseDriver;
use Windwalker\Model\DatabaseModelInterface;
use Windwalker\Registry\Registry;

/**
 * The DatabaseModel class.
 * 
 * @since  2.0
 */
class DatabaseModel extends Model implements DatabaseModelInterface
{
	/**
	 * Property db.
	 *
	 * @var  DatabaseDriver
	 */
	protected $db = null;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry       $state The model state.
	 * @param   DatabaseDriver $db    The database adapter.
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $state = null, DatabaseDriver $db = null)
	{
		$this->db = $db ? : Ioc::getDatabase();

		parent::__construct($state);
	}

	/**
	 * getDb
	 *
	 * @return  DatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * setDb
	 *
	 * @param   DatabaseDriver $db
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDb($db)
	{
		$this->db = $db;

		return $this;
	}
}
 