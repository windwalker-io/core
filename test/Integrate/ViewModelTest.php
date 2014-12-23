<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Integrate;

use Windwalker\Core\Model\DatabaseModel;
use Windwalker\Core\Test\AbstractBaseTestCase;
use Windwalker\Core\Test\Integrate\Model\FlowerModel;
use Windwalker\Core\Test\Integrate\Model\StubModel;
use Windwalker\Core\Test\Integrate\View\Stub\StubHtmlView;
use Windwalker\Core\View\HtmlView;
use Windwalker\Filesystem\Path;
use Windwalker\Registry\Registry;

/**
 * The ControllerViewTest class.
 * 
 * @since  {DEPLOY_VERSION}
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

		$view = new HtmlView;

		$model = new StubModel;
		$model->setConfig(new Registry(array('name' => 'foo')));
		$view->setModel($model);

		$this->assertTrue($view->getModel() instanceof StubModel);
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
		$this->assertEquals(array(1,2,3,4), $this->view->model->getList());
		$this->assertEquals(null, $this->view->model->getData());
		$this->assertEquals(null, $this->view->model->loadData());

		try
		{
			$this->view->model->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}

		$this->assertEquals('Flower', $this->view->models['flower']->getFlower());
		$this->assertEquals('Sakura', $this->view->models['flower']->getSakura());
		$this->assertEquals(null, $this->view->models['flower']->getData());
		$this->assertEquals(null, $this->view->models['flower']->loadData());

		try
		{
			$this->view->models['flower']->fetchData();
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('BadMethodCallException', $e);
		}
	}
}
