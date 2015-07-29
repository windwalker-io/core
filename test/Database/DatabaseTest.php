<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Database;

use Windwalker\Core\Ioc;
use Windwalker\Core\Test\AbstractDatabaseTestCase;
use Windwalker\Database\Test\AbstractDatabaseCase;
use Windwalker\DataMapper\DataMapper;
use Windwalker\Query\Mysql\MysqlQueryBuilder;

/**
 * The DatabaseTest class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class DatabaseTest extends AbstractDatabaseTestCase
{
	/**
	 * Property driver.
	 *
	 * @var  string
	 */
	static $driver = 'mysql';

	/**
	 * testGetDatabase
	 *
	 * @return  void
	 */
	public function testGetDatabase()
	{
		$db = Ioc::getDatabase();

		$r = $db->getDatabase(static::$dbname)->tableExists('#__flower');

		$this->assertTrue($r);
	}

	/**
	 * testGetDataMapper
	 *
	 * @return  void
	 */
	public function testGetDataMapper()
	{
		$datamapper = new DataMapper('#__flower');

		$data = $datamapper->findOne();

		$this->assertEquals(1, $data['id']);
	}
}
