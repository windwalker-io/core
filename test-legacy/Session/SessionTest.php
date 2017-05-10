<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Session;

use Windwalker\Core\Ioc;
use Windwalker\Core\Provider\SessionProvider;
use Windwalker\Test\TestCase\AbstractBaseTestCase;
use Windwalker\Session\Session;
use Windwalker\Test\TestHelper;

/**
 * The SessionTest class.
 * 
 * @since  2.1.1
 */
class SessionTest extends AbstractBaseTestCase
{
	/**
	 * setUp
	 *
	 * @return  void
	 */
	public function setUp()
	{
	}

	/**
	 * testDatabaseSession
	 *
	 * @return  void
	 */
	public function testDatabaseSession()
	{
		$container = Ioc::getContainer();

		$config = $container->get('config');

		$config['session.handler'] = 'database';
		$config['session.database'] = array(
			'table'    => 'flower',
			'id_col'   => 'sakura_id',
			'data_col' => 'sakura_data',
			'time_col' => 'sakura_time'
		);

		$container->registerServiceProvider(new SessionProvider);

		$session = $container->get('session');

		// TODO: Session dose not support get handler now, we'll complete this test after Session support it.
		// Now we just make sure it will not break.
		$this->assertTrue($session instanceof Session);
	}

	/**
	 * testNativeSession
	 *
	 * @return  void
	 */
	public function testNativeSession()
	{
		$this->markTestIncomplete('This test is incomplete.');
	}
}
