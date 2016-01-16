<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Migration;

use Phinx\Migration\AbstractMigration;

/**
 * Class MigrationHelper
 *
 * @since 1.0
 */
class MigrationHelper
{
	/**
	 * getQuery
	 *
	 * @param AbstractMigration $migration
	 *
	 * @return
	 */
	public static function getQuery(AbstractMigration $migration)
	{
		$driver = static::getDriver($migration);

		$class = 'Windwalker\\Query\\' . ucfirst($driver) . '\\' . ucfirst($driver) . 'Query';

		return new $class(static::getConnector($migration));
	}

	/**
	 * getConnector
	 *
	 * @param AbstractMigration $migration
	 *
	 * @return  \PDO
	 */
	public static function getConnector(AbstractMigration $migration)
	{
		/** @var $pdo \PDO */
		$pdo = $migration->getAdapter()->getConnection();

		return $pdo;
	}

	/**
	 * getDriver
	 *
	 * @param AbstractMigration $migration
	 *
	 * @return  string
	 */
	public static function getDriver(AbstractMigration $migration)
	{
		$pdo = static::getConnector($migration);

		return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}
}
 