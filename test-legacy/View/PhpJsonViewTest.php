<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\View;

use Windwalker\Core\View\RegistryView;

/**
 * Test class of PhpJsonView
 *
 * @since {DEPLOY_VERSION}
 */
class PhpJsonViewTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var RegistryView
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
		$this->instance = new RegistryView;
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
	 * testRender
	 *
	 * @return  void
	 */
	public function testRender()
	{
		$this->instance['foo.bar'] = 'yoo';

		$this->assertJsonStringEqualsJsonString('{"foo":{"bar":"yoo"}}', $this->instance->render());
	}

	/**
	 * Method to test getOptions().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpJsonView::getOptions
	 * @TODO   Implement testGetOptions().
	 */
	public function testGetOptions()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setOptions().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpJsonView::setOptions
	 * @TODO   Implement testSetOptions().
	 */
	public function testSetOptions()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getDepth().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpJsonView::getDepth
	 * @TODO   Implement testGetDepth().
	 */
	public function testGetDepth()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setDepth().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpJsonView::setDepth
	 * @TODO   Implement testSetDepth().
	 */
	public function testSetDepth()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
