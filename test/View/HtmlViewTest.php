<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\View;

use Windwalker\Core\Model\Model;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Test\Mvc\Model\StubModel;
use Windwalker\Core\Test\Mvc\MvcPackage;
use Windwalker\Core\Test\Mvc\View\Stub\StubHtmlView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Data\Data;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;

/**
 * Test class of HtmlView
 *
 * @since {DEPLOY_VERSION}
 */
class HtmlViewTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var HtmlView
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
		$this->instance = new HtmlView;
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
	 * Method to test getData().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::getData
	 */
	public function testGetAndSetData()
	{
		$this->assertInstanceOf('Windwalker\Data\Data', $this->instance->getData());

		$data = new Data;

		$this->instance->setData($data);

		$this->assertSame($data, $this->instance->getData());
	}

	/**
	 * Method to test render().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::render
	 */
	public function testRender()
	{
		Ioc::factory()->set('package.mvc', new MvcPackage);

		$config = new Registry(
			array(
				'name' => 'stub',
				'package' => array(
					'name' => 'mvc'
				)
			)
		);

		$this->instance->setConfig($config);

		$this->assertEquals('<h1>Flower</h1>', trim($this->instance->setLayout('flower')->render()));
	}

	/**
	 * Method to test getName().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::getName
	 */
	public function testGetAndSetName()
	{
		$this->assertEquals('default', $this->instance->getName());

		$config = new Registry(
			array(
				'name' => 'foo'
			)
		);

		$this->instance->setConfig($config);

		$this->assertEquals('foo', $this->instance->getName());

		$view = new StubHtmlView;

		$this->assertEquals('stub', $view->getName());

		$view->setName('flower');

		$this->assertEquals('flower', $view->getName());
	}

	/**
	 * Method to test getPackage().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::getPackage
	 * @TODO   Implement testGetPackage().
	 */
	public function testGetAndSetPackage()
	{
		$this->instance->setPackage(new MvcPackage);

		$this->assertEquals('mvc', $this->instance->config['package.name']);
	}

	/**
	 * Method to test getConfig().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::getConfig
	 */
	public function testGetAndSetConfig()
	{
		$config = new Registry(
			array(
				'name' => 'sakura',
				'package' => array(
					'name' => 'flower',
					'path' => 'foo/bar/baz'
				)
			)
		);

		$this->instance->setConfig($config);

		$this->assertEquals('sakura', $this->instance->getName());

		$this->assertSame($config, $this->instance->getConfig());

		$this->assertTrue($this->instance->getPackage() instanceof NullPackage);
	}

	/**
	 * Method to test __get().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::__get
	 */
	public function test__get()
	{
		$this->assertTrue($this->instance->config instanceof Registry);

		$this->assertNull($this->instance->flower);
	}

	/**
	 * Method to test getModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::getModel
	 */
	public function testGetAndSetModel()
	{
		$view = $this->instance;

		$view->setModel(new StubModel);

		$view->setModel(new Model);

		// Get default model
		$this->assertTrue($view->getModel() instanceof StubModel);

		// Get by name
		$this->assertTrue($view->getModel('stub') instanceof StubModel);

		$model = new Model;

		$view->setModel($model, HtmlView::DEFAULT_MODEL);

		// Get default model
		$this->assertSame($model, $view->getModel());
	}

	/**
	 * Method to test removeModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\HtmlView::removeModel
	 */
	public function testRemoveModel()
	{
		$view = $this->instance;

		$view->setModel(new StubModel);

		$view->removeModel('stub');

		$this->assertTrue($view->getModel()->get('is.null'));
		$this->assertTrue($view->getModel('stub')->get('is.null'));
	}
}
