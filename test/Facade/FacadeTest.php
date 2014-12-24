<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Facade;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Test\Facade\Stub\StubConfigFacade;
use Windwalker\Core\Test\Facade\Stub\StubMvcFacade;
use Windwalker\Core\Ioc;
use Windwalker\Core\Test\Mvc\MvcPackage;

/**
 * Test class of Facade
 *
 * @since {DEPLOY_VERSION}
 */
class FacadeTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Property package.
	 *
	 * @var AbstractPackage
	 */
	protected static $package;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return  void
	 */
	public static function setUpBeforeClass()
	{
		$mvc = new MvcPackage;

		$mvc->initialise();

		if (!Ioc::exists('package.mvc'))
		{
			Ioc::factory()->set('package.mvc', $mvc);
		}

		static::$package = $mvc;
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return  void
	 */
	public static function tearDownAfterClass()
	{
		Ioc::factory()->share('package.mvc', null);

		static::$package = null;
	}

	/**
	 * Method to test __callStatic().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::__callStatic
	 */
	public function test__callStatic()
	{
		$this->assertEquals(static::$package->getDir(), StubMvcFacade::getDir());

		$this->assertEquals('UTC', StubConfigFacade::get('system.timezone'));
	}

	/**
	 * Method to test setContainer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::setContainer
	 * @TODO   Implement testSetContainer().
	 */
	public function testSetContainer()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test reset().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::reset
	 * @TODO   Implement testReset().
	 */
	public function testReset()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getKey().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::getKey
	 * @TODO   Implement testGetKey().
	 */
	public function testGetKey()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setKey().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::setKey
	 * @TODO   Implement testSetKey().
	 */
	public function testSetKey()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getName().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::getName
	 * @TODO   Implement testGetName().
	 */
	public function testGetName()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setName().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Facade\Facade::setName
	 * @TODO   Implement testSetName().
	 */
	public function testSetName()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
