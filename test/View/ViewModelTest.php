<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\View;

use Windwalker\Core\Model\DatabaseModel;
use Windwalker\Core\Test\Mvc\Model\FlowerModel;
use Windwalker\Core\Test\Mvc\Model\StubModel;
use Windwalker\Core\View\ViewModel;
use Windwalker\Registry\Registry;

/**
 * Test class of ViewModel
 *
 * @since {DEPLOY_VERSION}
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var ViewModel
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
		$this->instance = new ViewModel;
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
	 * Method to test getModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::getModel
	 * @TODO   Implement testGetModel().
	 */
	public function testGetAndSetModel()
	{
		$this->instance->setModel(new StubModel);
		$this->instance->setModel(new FlowerModel);

		$this->assertTrue($this->instance->getModel() instanceof StubModel);
		$this->assertTrue($this->instance->getModel('stub') instanceof StubModel);
		$this->assertTrue($this->instance->getModel('flower') instanceof DatabaseModel);

		$viewModel = new ViewModel;

		$model = new StubModel;
		$model->setConfig(new Registry(array('name' => 'foo')));
		$viewModel->setModel($model);

		$this->assertTrue($viewModel->getModel() instanceof StubModel);
	}

	/**
	 * Method to test removeModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::removeModel
	 */
	public function testRemoveModel()
	{
		$viewModel = $this->instance;

		$viewModel->setModel(new StubModel);

		$viewModel->removeModel('stub');

		$this->assertTrue($viewModel->getModel()->get('is.null'));
		$this->assertTrue($viewModel->getModel('stub')->get('is.null'));
	}

	/**
	 * Method to test exists().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::exists
	 */
	public function testExists()
	{
		$viewModel = $this->instance;

		$viewModel->setModel(new StubModel);

		$this->assertTrue($this->instance->exists('stub'));
		$this->assertFalse($this->instance->exists('foo'));
	}

	/**
	 * Method to test __call().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::__call
	 * @TODO   Implement test__call().
	 */
	public function test__call()
	{
		$this->instance->setModel(new StubModel);
		$this->instance->setModel(new FlowerModel);

		$this->assertEquals('Item', $this->instance->getItem());
		$this->assertEquals(array(1,2,3,4), $this->instance->getList());
		$this->assertEquals(null, $this->instance->getData());
		$this->assertEquals(null, $this->instance->loadData());

		try
		{
			$this->instance->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}
	}

	/**
	 * Method to test offsetExists().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::offsetExists
	 */
	public function testOffsetExists()
	{
		$viewModel = $this->instance;

		$viewModel->setModel(new StubModel);

		$this->assertTrue(isset($viewModel['stub']));
	}

	/**
	 * Method to test offsetGet().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::offsetGet
	 */
	public function testOffsetGet()
	{
		$this->instance->setModel(new StubModel);
		$this->instance->setModel(new FlowerModel);

		$this->assertEquals('Flower', $this->instance['flower']->getFlower());
		$this->assertEquals('Sakura', $this->instance['flower']->getSakura());
		$this->assertEquals(null, $this->instance['flower']->getData());
		$this->assertEquals(null, $this->instance['flower']->loadData());
		$this->assertEquals(null, $this->instance['none']->loadData());
		$this->assertEquals(null, $this->instance['none']->getData());

		try
		{
			$this->instance['flower']->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}
	}

	/**
	 * Method to test offsetSet().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::offsetSet
	 * @TODO   Implement testOffsetSet().
	 */
	public function testOffsetSet()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test offsetUnset().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::offsetUnset
	 * @TODO   Implement testOffsetUnset().
	 */
	public function testOffsetUnset()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test count().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::count
	 */
	public function testCount()
	{
		$viewModel = $this->instance;

		$viewModel->setModel(new StubModel);

		$this->assertEquals(1, count($viewModel));
	}

	/**
	 * Method to test getNullModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::getNullModel
	 * @TODO   Implement testGetNullModel().
	 */
	public function testGetNullModel()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setNullModel().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\View\ViewModel::setNullModel
	 * @TODO   Implement testSetNullModel().
	 */
	public function testSetNullModel()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
