<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Mvc;

use Windwalker\Core\Model\DatabaseModel;
use Windwalker\Test\TestCase\AbstractBaseTestCase;
use Windwalker\Core\Test\Mvc\Model\FlowerModel;
use Windwalker\Core\Test\Mvc\Model\StubModel;
use Windwalker\Core\Test\Mvc\View\Stub\StubHtmlView;
use Windwalker\Core\View\PhpHtmlView;
use Windwalker\Filesystem\Path;
use Windwalker\Structure\Structure;

/**
 * The ControllerViewTest class.
 * 
 * @since  2.1.1
 */
class ViewModelTest extends AbstractBaseTestCase
{
	/**
	 * Property instance.
	 *
	 * @var StubHtmlView
	 */
	protected $view;

	/**
	 * Property model.
	 *
	 * @var StubModel
	 */
	protected $model;

	/**
	 * setUp
	 *
	 * @return  void
	 */
	public function setUp()
	{
		$this->view = new StubHtmlView;

		$this->model = new StubModel;
	}

	/**
	 * testInjectModel
	 *
	 * @return  void
	 */
	public function testInjectModel()
	{
		$this->view->setModel($this->model);
		$this->view->setModel(new FlowerModel);

		$this->assertTrue($this->view->getModel() instanceof StubModel);
		$this->assertTrue($this->view->getModel('stub') instanceof StubModel);
		$this->assertTrue($this->view->getModel('flower') instanceof DatabaseModel);

		// Test inject with custom name
		$this->view->setModel($flowerModel = new FlowerModel, false, 'butterfly');

		$this->assertSame($flowerModel, $this->view->getModel('butterfly'));

		$view = new PhpHtmlView;

		$model = new StubModel;
		$model->setConfig(new Structure(array('name' => 'foo')));
		$view->setModel($model);

		$this->assertTrue($view->getModel() instanceof StubModel);
	}

	/**
	 * testInjectFirstModelNotDefault
	 *
	 * @return  void
	 */
	public function testInjectFirstModelNotDefault()
	{
		$this->view->setModel($this->model, false);
		$this->view->setModel($flowerModel = new FlowerModel);

		$this->assertSame($flowerModel, $this->view->getModel());
		$this->assertSame($flowerModel, $this->view->getModel('flower'));
		$this->assertSame($this->model, $this->view->getModel('stub'));
	}

	/**
	 * testGetData
	 *
	 * @return  void
	 */
	public function testGetData()
	{
		$this->view->setModel($this->model);
		$this->view->setModel(new FlowerModel);

		$this->assertEquals('Item', $this->view->model->getItem());
		$this->assertEquals('Item', $this->view->model->get('Item'));
		$this->assertEquals(array(1,2,3,4), $this->view->model->getList());
		$this->assertEquals(null, $this->view->model->getData());
		$this->assertEquals(null, $this->view->model->loadData());
		$this->assertEquals(null, $this->view->model->load('Data'));

		try
		{
			$this->view->model->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}

		$this->assertEquals('Flower', $this->view->model['flower']->getFlower());
		$this->assertEquals('Sakura', $this->view->model['flower']->getSakura());
		$this->assertEquals('Flower', $this->view->model->get('Flower', 'flower'));
		$this->assertEquals('Sakura', $this->view->model->get('Sakura', 'flower'));
		$this->assertEquals(null, $this->view->model['flower']->getData());
		$this->assertEquals(null, $this->view->model['flower']->loadData());
		$this->assertEquals(null, $this->view->model['none']->loadData());
		$this->assertEquals(null, $this->view->model['none']->getData());

		try
		{
			$this->view->model['flower']->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}
	}
}
