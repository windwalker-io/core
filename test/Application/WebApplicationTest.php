<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Application;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\DI\ServiceProviderInterface;

/**
 * Test class of WebApplication
 *
 * @since 2.1.1
 */
class WebApplicationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var WebApplication
	 */
	protected $instance;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$_SERVER['PHP_SELF'] = '/foo/bar';
		$_SERVER['SCRIPT_NAME'] = '/foo/bar';

		$this->instance = new WebApplication(null, null, array('name' => 'test'));
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
	 * Method to test loadProviders().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::loadProviders
	 */
	public function testLoadProviders()
	{
		$providers = $this->instance->loadProviders();

		$this->assertTrue(array_shift($providers) instanceof ServiceProviderInterface);
	}

	/**
	 * Method to test loadPackages().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::loadPackages
	 */
	public function testLoadPackages()
	{
		// $packages = $this->instance->loadPackages();

		// $this->assertTrue(array_shift($packages) instanceof AbstractPackage);
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test execute().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::execute
	 */
	public function testExecute()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getController().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::route
	 * @TODO   Implement testGetController().
	 */
	public function testGetController()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test matchRoute().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::matchRoute
	 * @TODO   Implement testMatchRoute().
	 */
	public function testMatchRoute()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getRouter().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::getRouter
	 * @TODO   Implement testGetRouter().
	 */
	public function testGetRouter()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test addFlash().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::addFlash
	 * @TODO   Implement testAddFlash().
	 */
	public function testAddFlash()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test redirect().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::redirect
	 * @TODO   Implement testRedirect().
	 */
	public function testRedirect()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test initUri().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::initUri
	 * @TODO   Implement testInitUri().
	 */
	public function testInitUri()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test triggerEvent().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::triggerEvent
	 * @TODO   Implement testTriggerEvent().
	 */
	public function testTriggerEvent()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test prepareSystemPath().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Application\WebApplication::prepareSystemPath
	 * @TODO   Implement testPrepareSystemPath().
	 */
	public function testPrepareSystemPath()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
