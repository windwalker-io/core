<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\View;

use Windwalker\Core\View\PhpXmlView;

/**
 * Test class of PhpXmlView
 *
 * @since {DEPLOY_VERSION}
 */
class PhpXmlViewTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var PhpXmlView
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
		$this->instance = new PhpXmlView;
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
		$view = new PhpXmlView(array(
			'foo' => 'bar',
			'flower' => array(
				'sakura' => 'beautiful'
			)
		));

		$xml = <<<XML
<?xml version="1.0"?>
<windwalker>
	<node name="foo" type="string">bar</node>
	<node name="flower" type="array">
		<node name="sakura" type="string">beautiful</node>
	</node>
</windwalker>
XML;

		$this->assertXmlStringEqualsXmlString($xml, $view->render());

		$data = $this->instance->getData();

		$foo = $data->addChild('foo');
		$foo->addAttribute('data-name', 'bar');

		$xml = <<<XML
<?xml version="1.0"?>
<windwalker>
    <foo data-name="bar"/>
</windwalker>
XML;

		$this->assertXmlStringEqualsXmlString($xml, $this->instance->render());
	}


	/**
	 * Method to test getData().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpXmlView::getData
	 * @TODO   Implement testGetData().
	 */
	public function testGetData()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setData().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\PhpXmlView::setData
	 * @TODO   Implement testSetData().
	 */
	public function testSetData()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
