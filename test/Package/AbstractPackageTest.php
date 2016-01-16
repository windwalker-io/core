<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Package;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Test\Mvc\MvcPackage;

/**
 * Test class of AbstractPackage
 *
 * @since 2.1.1
 */
class AbstractPackageTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var AbstractPackage
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
		$this->instance = new MvcPackage;
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
	 * Method to test initialise().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::initialise
	 * @TODO   Implement testInitialise().
	 */
	public function testInitialise()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test buildRoute().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::buildRoute
	 * @TODO   Implement testBuildRoute().
	 */
	public function testBuildRoute()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getContainer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::getContainer
	 * @TODO   Implement testGetContainer().
	 */
	public function testGetContainer()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setContainer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::setContainer
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
	 * Method to test getName().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::getName
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
	 * @covers Windwalker\Core\Package\AbstractPackage::setName
	 * @TODO   Implement testSetName().
	 */
	public function testSetName()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test get().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::get
	 * @TODO   Implement testGet().
	 */
	public function testGet()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test set().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::set
	 * @TODO   Implement testSet().
	 */
	public function testSet()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test registerProviders().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::registerProviders
	 */
	public function testRegisterProviders()
	{
		$mvc = new MvcPackage();
		$mvc->initialise();

		$container = Ioc::factory('mvc');

		$this->assertEquals('Flower Sakura', $container->get('flower.sakura'));
		$this->assertEquals('Flower Sakura', Ioc::get('flower.sakura', 'mvc'));
	}

	/**
	 * Method to test registerListeners().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::registerListeners
	 * @TODO   Implement testRegisterListeners().
	 */
	public function testRegisterListeners()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test loadConfig().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::loadConfig
	 * @TODO   Implement testLoadConfig().
	 */
	public function testLoadConfig()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test loadRouting().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::loadRouting
	 * @TODO   Implement testLoadRouting().
	 */
	public function testLoadRouting()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getFile().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::getFile
	 * @TODO   Implement testGetFile().
	 */
	public function testGetFile()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getDir().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::getDir
	 * @TODO   Implement testGetDir().
	 */
	public function testGetDir()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test registerCommands().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Package\AbstractPackage::registerCommands
	 * @TODO   Implement testRegisterCommands().
	 */
	public function testRegisterCommands()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
