<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Model;

use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\DataMapper\DataMapper;
use Windwalker\Record\Record;

/**
 * The DatabaseRepositoryInterface class.
 *
 * @since  {DEPLOY_VERSION}
 */
interface DatabaseRepositoryInterface
{
	/**
	 * getRecord
	 *
	 * @param   string $name
	 *
	 * @return  Record
	 */
	public function getRecord($name = null);

	/**
	 * getDataMapper
	 *
	 * @param string $name
	 *
	 * @return  DataMapper
	 */
	public function getDataMapper($name = null);

	/**
	 * getDb
	 *
	 * @return  AbstractDatabaseDriver
	 */
	public function getDb();

	/**
	 * setDb
	 *
	 * @param   AbstractDatabaseDriver $db
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDb($db);

	/**
	 * transactionStart
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionStart($nested = true);

	/**
	 * transactionCommit
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionCommit($nested = true);

	/**
	 * transactionRollback
	 *
	 * @param boolean $nested
	 *
	 * @return  static
	 */
	public function transactionRollback($nested = true);
}
