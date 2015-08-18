<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Model;

use Windwalker\Core\Ioc;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
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
	 * @var  AbstractDatabaseDriver
	 */
	protected $db = null;

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry               $state The model state.
	 * @param   AbstractDatabaseDriver $db    The database adapter.
	 *
	 * @since   1.0
	 */
	public function __construct(Registry $state = null, AbstractDatabaseDriver $db = null)
	{
		$this->db = $db ? : Ioc::getDatabase();

		parent::__construct($state);
	}

	/**
	 * getDb
	 *
	 * @return  AbstractDatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * setDb
	 *
	 * @param   AbstractDatabaseDriver $db
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDb($db)
	{
		if (!$db instanceof AbstractDatabaseDriver)
		{
			throw new \InvalidArgumentException('$db should be AbstractDatabaseDriver.');
		}

		$this->db = $db;

		return $this;
	}

	/**
	 * transactionStart
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionStart($nested = true)
	{
		$this->db->getTransaction($nested)->start();

		return $this;
	}

	/**
	 * transactionCommit
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionCommit($nested = true)
	{
		$this->db->getTransaction($nested)->commit();

		return $this;
	}

	/**
	 * transactionRollback
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionRollback($nested = true)
	{
		$this->db->getTransaction($nested)->start();

		return $this;
	}
}
