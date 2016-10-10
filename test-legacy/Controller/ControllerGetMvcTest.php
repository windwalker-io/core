<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Controller;

use Windwalker\Core\Mvc\ModelResolver;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Test\Controller\Mock\Controller\Mock\StubController;
use Windwalker\Core\Test\Mvc\MvcPackage;
use Windwalker\Core\Test\Package\Mock\MockPackage;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * Test class of Controller
 *
 * @since 3.0.1
 */
class ControllerGetMvcTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var StubController
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
		$this->instance = new StubController;

		PackageHelper::addPackage('mvc', new MvcPackage);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		PackageHelper::removePackage('mvc');

		$this->instance->getPackage()->getMvcResolver()->reset();
	}

	/**
	 * testGetModel
	 *
	 * @return  void
	 */
	public function testGetModel()
	{
		$mvcPackage = PackageHelper::getPackage('mvc');

		// With no package
		// -------------------------------------------

		// Find not exists model
		$model = $this->instance->getModel('Yoo');

		$this->assertEquals('Windwalker\Core\Model\Model', get_class($model));

		// Find without package
		$model = $this->instance->getModel('Stub');

		$this->assertEquals('Windwalker\Core\Test\Controller\Mock\Model\StubModel', get_class($model));

		$this->instance->getContainer()->remove(ModelResolver::getDIKey('Stub'));

		// With package
		// -------------------------------------------
		$this->instance->setPackage(new MockPackage);

		// Find self model
		$model = $this->instance->getModel('Stub');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Controller\Mock\Model\StubModel',
			$model,
			'Controller::getModel() should return model in self folder'
		);

		// Add namespace path
		$this->instance->getPackage()
			->getMvcResolver()
			->addNamespace(ReflectionHelper::getNamespaceName($mvcPackage));

		// Test find other package's model
		$model = $this->instance->getModel('Flower');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Mvc\Model\FlowerModel',
			$model,
			'Controller::getModel() should return model in other package\'s folder'
		);

		// Reset controller
		$this->instance->getContainer()->remove(ModelResolver::getDIKey('Stub'));

		// Test find model with same name
		$model = $this->instance->getModel('Stub');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Mvc\Model\StubModel',
			$model,
			'Controller::getModel() should return model in other package\'s folder'
		);
	}

	/**
	 * testGetModel
	 *
	 * @return  void
	 */
	public function testGetView()
	{
		$mvcPackage = PackageHelper::getPackage('mvc');

		// With no package
		// -------------------------------------------

		// Find not exists view
		$view = $this->instance->getView('Yoo');

		$this->assertEquals('Windwalker\Core\View\PhpHtmlView', get_class($view));

		// Find without package
		$view = $this->instance->getView('Stub');

		$this->assertEquals('Windwalker\Core\Test\Controller\Mock\View\Stub\StubHtmlView', get_class($view));

		$this->instance->getContainer()->remove(ViewResolver::getDIKey('Stub.Html'));

		// With package
		// -------------------------------------------
		$this->instance->setPackage(new MockPackage);

		// Find self view
		$view = $this->instance->getView('Stub');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Controller\Mock\View\Stub\StubHtmlView',
			$view,
			'Controller::getView() should return view in self folder'
		);

		// Add namespace path
		$this->instance->getPackage()
			->getMvcResolver()
			->addNamespace(ReflectionHelper::getNamespaceName($mvcPackage));

		// Test find other package's view
		$view = $this->instance->getView('Flower');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Mvc\View\Flower\FlowerHtmlView',
			$view,
			'Controller::getView() should return view in other package\'s folder'
		);

		// Reset controller
		$this->instance->getContainer()->remove(ViewResolver::getDIKey('Stub.Html'));

		// Test find view with same name
		$view = $this->instance->getView('Stub');

		$this->assertInstanceOf(
			'Windwalker\Core\Test\Mvc\View\Stub\StubHtmlView',
			$view,
			'Controller::getView() should return view in other package\'s folder'
		);
	}
}
