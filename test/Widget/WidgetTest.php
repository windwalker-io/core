<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Widget;

use Windwalker\Core\Ioc;
use Windwalker\Filesystem\Path;
use Windwalker\Test\TestCase\AbstractBaseTestCase;
use Windwalker\Core\Widget\Widget;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Test\TestHelper;
use Windwalker\Utilities\Queue\Priority;

/**
 * Test class of Widget
 *
 * @since 2.1.1
 */
class WidgetTest extends AbstractBaseTestCase
{
	/**
	 * Test instance.
	 *
	 * @var Widget
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
		$this->instance = new Widget('test.test');
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
	 * Method to test render().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::render
	 * @TODO   Implement testRender().
	 */
	public function testRender()
	{
		$html = $this->instance->render();

		$this->assertEquals('<h1>Test</h1>', trim($html));

		$widget = new Widget('page');

		$this->assertEquals('<h1>TEST PAGE</h1>', trim($widget->render()));

		$widget = new Widget('_global.test');

		$this->assertEquals('<h1>Test</h1>', trim($widget->render()));

		$widget = new Widget('widget.test');

		$this->assertEquals('Flower: Sakura', trim($widget->render(array('flower' => 'Sakura'))));
	}

	/**
	 * Method to test getLayout().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::getLayout
	 * @covers Windwalker\Core\Widget\Widget::setLayout
	 */
	public function testGetAndSetLayout()
	{
		$this->assertEquals('test.test', $this->instance->getLayout());

		$this->instance->setLayout('aaa.bbb');

		$this->assertEquals('aaa.bbb', $this->instance->getLayout());
	}

	/**
	 * Method to test getRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::getRenderer
	 */
	public function testGetAndSetRenderer()
	{
		$this->assertTrue($this->instance->getRenderer() instanceof PhpRenderer);

		$this->instance->setRenderer(new BladeRenderer);

		$this->assertTrue($this->instance->getRenderer() instanceof BladeRenderer);
	}

	/**
	 * Method to test addPath().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::addPath
	 */
	public function testAddPath()
	{
		$this->instance->addPath('foo/bar/baz', Priority::LOW);
		$this->instance->addPath('flower/sakura', Priority::NORMAL);

		TestHelper::invoke($this->instance, 'registerPaths');

		$paths = $this->instance->getPaths()->toArray();

		$this->assertPathEquals('flower/sakura', $paths[0]);
		$this->assertPathEquals('foo/bar/baz', $paths[1]);
	}

	/**
	 * testDefaultPaths
	 *
	 * @return  void
	 */
	public function testDefaultPaths()
	{
		TestHelper::invoke($this->instance, 'registerPaths');

		$paths = $this->instance->getPaths()->toArray();

		$this->assertPathEquals(WINDWALKER_ROOT . '/templates', $paths[0]);
		$this->assertPathEquals(realpath(WINDWALKER_ROOT . '/../../src/Core/Resources/Templates'), $paths[1]);

		$this->instance->reset()->setPackage('mvc');

		TestHelper::invoke($this->instance, 'registerPaths');

		$paths = $this->instance->getPaths()->toArray();

		$this->assertPathEquals(realpath(WINDWALKER_ROOT . '/../Mvc/Templates'), $paths[0]);
		$this->assertPathEquals(WINDWALKER_ROOT . '/templates', $paths[1]);
		$this->assertPathEquals(realpath(WINDWALKER_ROOT . '/../../src/Core/Resources/Templates'), $paths[2]);
	}

	/**
	 * testDefaultPackage
	 *
	 * @return  void
	 */
	public function testDefaultPackage()
	{
		$config = Ioc::getConfig();

		$config->set('route.package', 'mvc');

		$widget = new Widget('test.test');

		$this->assertEquals('mvc', $widget->getPackage()->getName());

		$config->set('route.package', null);
	}

	/**
	 * Method to test getPaths().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::getPaths
	 * @TODO   Implement testGetPaths().
	 */
	public function testGetPaths()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setPaths().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::setPaths
	 * @TODO   Implement testSetPaths().
	 */
	public function testSetPaths()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test isDebug().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::isDebug
	 * @TODO   Implement testIsDebug().
	 */
	public function testIsDebug()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setDebug().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\Widget::setDebug
	 * @TODO   Implement testSetDebug().
	 */
	public function testSetDebug()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * assertPathEquals
	 *
	 * @param  mixed   $expected
	 * @param  mixed   $actual
	 * @param  string  $msg
	 *
	 * @return  void
	 */
	protected function assertPathEquals($expected, $actual, $msg = null)
	{
		$this->assertEquals(
			Path::clean($expected),
			Path::clean($actual),
			$msg
		);
	}
}
