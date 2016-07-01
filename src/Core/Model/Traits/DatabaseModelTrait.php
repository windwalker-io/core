<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model\Traits;

use Windwalker\Core\Ioc;
use Windwalker\Core\Model\ModelRepository;
use Windwalker\Database\Driver\AbstractDatabaseDriver;

/**
 * The DatabaseModelTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait DatabaseModelTrait
{
	/**
	 * Property db.
	 *
	 * @var  AbstractDatabaseDriver
	 */
	protected $db;

	/**
	 * bootDatabaseModelTrait
	 *
	 * @param ModelRepository $model
	 *
	 * @return  void
	 */
	public function bootDatabaseModelTrait(ModelRepository $model)
	{
		// Prepare DB
		$this->getDb();
	}

	/**
	 * getDb
	 *
	 * @return  AbstractDatabaseDriver
	 */
	public function getDb()
	{
		if (!$this->db)
		{
			$this->db = $this->source;

			if (!$this->db instanceof AbstractDatabaseDriver)
			{
				$this->db = Ioc::getDatabase();
			}
		}

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
		$this->getDb()->getTransaction($nested)->start();

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
		$this->getDb()->getTransaction($nested)->commit();

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
		$this->getDb()->getTransaction($nested)->start();

		return $this;
	}
}
