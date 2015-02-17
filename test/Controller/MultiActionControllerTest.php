<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Controller;

use Windwalker\Core\Test\AbstractBaseTestCase;
use Windwalker\Core\Test\Controller\Stub\StubMultiActionController;

/**
 * The MultiActionControllerTest class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class MultiActionControllerTest extends AbstractBaseTestCase
{
	/**
	 * Property instance.
	 *
	 * @var StubMultiActionController
	 */
	protected $instance;

	/**
	 * setUp
	 *
	 * @return  void
	 */
	public function setUp()
	{
		$this->instance = new StubMultiActionController;
	}

	/**
	 * testIndexAction
	 *
	 * @return  void
	 */
	public function testIndexAction()
	{
		$this->assertEquals('index', $this->instance->execute());
	}

	/**
	 * testNoAction
	 *
	 * @expectedException \LogicException
	 *
	 * @return  void
	 */
	public function testNoAction()
	{
		$this->instance->setActionName('fooAction')->execute();
	}

	/**
	 * testExistsAction
	 *
	 * @return  void
	 */
	public function testExistsAction()
	{
		$this->instance->setActionName('flyAction')->setArguments(array(1000, 450));

		$this->assertEquals('Flying on 1000 km and speed: 450', $this->instance->execute());
	}
}