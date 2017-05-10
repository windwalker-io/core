<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Controller;

use Windwalker\Core\Mvc\ControllerResolver;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Test\Controller\Mock\MockPackage;
use Windwalker\Core\Test\Mvc\MvcPackage;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * Test class of ControllerResolver
 *
 * @since 3.0.1
 */
class ControllerResolverTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var ControllerResolver
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
		$this->instance = new ControllerResolver;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		PackageHelper::removePackage('mvc')->removePackage('mock');
	}

	/**
	 * Method to test resolveController().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::resolveController
	 */
	public function testResolveController()
	{
		$package = new MvcPackage;

		$class = $this->instance->resolve('Stub\StubController');

		$this->assertEquals('Windwalker\Core\Test\Mvc\Controller\Stub\StubController', $class);

		$class = $this->instance->resolve('Stub.StubController');

		$this->assertEquals('Windwalker\Core\Test\Mvc\Controller\Stub\StubController', $class);

		// Test Get alias
		PackageHelper::addPackage('mock', new MockPackage);

		$class = $this->instance->resolve('mock@Mock.StubController');

		$this->assertEquals('Windwalker\Core\Test\Controller\Mock\Controller\Mock\StubController', $class);

		PackageHelper::resolvePackage('mock');

		$this->instance->addClassAlias(
			'Windwalker\Core\Test\Mvc\Controller\Stub\StubController',
			'Windwalker\Core\Test\Controller\Mock\Controller\Mock\StubController'
		);

		$class = $this->instance->resolve('Stub.StubController');

		$this->assertEquals('Windwalker\Core\Test\Controller\Mock\Controller\Mock\StubController', $class);
	}

	/**
	 * Method to test findController().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::findController
	 */
	public function testFindController()
	{
		$this->instance->addNamespace('Flower\Sakura');
		$this->instance->addNamespace('Windwalker\Core\Test\Controller\Mock\Controller');

		$class = $this->instance->find('Mock\StubController');

		$this->assertEquals('Windwalker\Core\Test\Controller\Mock\Controller\Mock\StubController', $class);
		$this->assertFalse($this->instance->find('Foo\BarController'));
	}

	/**
	 * Method to test splitPackage().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::splitPackage
	 *
	 * @dataProvider splitPackageProvider
	 */
	public function testSplitPackage($input, $packageExpected, $nameExpected)
	{
		$package = $this->instance->splitPackage($input);

		$this->assertEquals($packageExpected, $package);
		$this->assertEquals($nameExpected, $input);
	}

	/**
	 * splitPackageProvider
	 *
	 * @return  array
	 */
	public function splitPackageProvider()
	{
		return [
			[
				'sakura', null, 'sakura'
			],
			[
				'flower@sakura', 'flower', 'sakura'
			],
			[
				'flower@', 'flower', null
			],
		];
	}

	/**
	 * Method to test getDIKey().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::getDIKey
	 */
	public function testGetDIKey()
	{
		$this->assertEquals('controller.name', $this->instance->getDIKey('name'));
		$this->assertEquals('controller.flower.sakura', $this->instance->getDIKey('flower\sakura'));
	}

	/**
	 * Method to test addNamespace().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::addNamespace
	 * @covers Windwalker\Core\Controller\ControllerResolver::dumpNamespaces
	 */
	public function testAddNamespace()
	{
		$this->instance->addNamespace('flower\sakura', Priority::LOW);
		$this->instance->addNamespace('flower\rose', Priority::HIGH);
		$this->instance->addNamespace('flower\lily', Priority::NORMAL);

		$expected = [
			'Flower\Rose',
			'Flower\Lily',
			'Flower\Sakura',
		];

		$this->assertEquals($expected, $this->instance->dumpNamespaces());
	}

	/**
	 * Method to test getNamespaces().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::getNamespaces
	 * @covers Windwalker\Core\Controller\ControllerResolver::setNamespaces
	 */
	public function testGetAndSetNamespaces()
	{
		$this->assertTrue($this->instance->getNamespaces() instanceof PriorityQueue);

		$this->instance->setNamespaces($queue = new PriorityQueue);

		$this->assertSame($queue, $this->instance->getNamespaces());

		$this->instance->setNamespaces(['foo', 'bar']);

		$this->assertTrue($this->instance->getNamespaces() instanceof PriorityQueue);

		$this->assertEquals(['foo', 'bar'], $this->instance->dumpNamespaces());
	}

	/**
	 * Method to test normalise().
	 *
	 * @param string $input
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::normalise
	 *
	 * @dataProvider normaliseProvider
	 */
	public function testNormalise($input)
	{
		$this->assertEquals('Flower\Sakura\GetController', $this->instance->normalise($input));
	}

	/**
	 * normaliseProvider
	 *
	 * @return  array
	 */
	public function normaliseProvider()
	{
		return [
			['Flower\Sakura/GetController'],
			['flower/sakura/getController'],
			['flower.sakura.GetController']
		];
	}

	/**
	 * Method to test resolveClassAlias().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::addClassAlias
	 * @covers Windwalker\Core\Controller\ControllerResolver::resolveClassAlias
	 */
	public function testAddAndResolveClassAlias()
	{
		$this->instance->addClassAlias('Foo', 'Bar');

		$this->assertEquals('Bar', $this->instance->resolveClassAlias('Bar'));
	}

	/**
	 * Method to test getClassAliases().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::getClassAliases
	 * @TODO   Implement testGetClassAliases().
	 */
	public function testGetClassAliases()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setClassAliases().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::setClassAliases
	 * @TODO   Implement testSetClassAliases().
	 */
	public function testSetClassAliases()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getPackageResolver().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Controller\ControllerResolver::getPackageResolver
	 * @TODO   Implement testGetPackageResolver().
	 */
	public function testGetPackageResolver()
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
	 * @covers Windwalker\Core\Controller\ControllerResolver::getContainer
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
	 * @covers Windwalker\Core\Controller\ControllerResolver::setContainer
	 * @TODO   Implement testSetContainer().
	 */
	public function testSetContainer()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
