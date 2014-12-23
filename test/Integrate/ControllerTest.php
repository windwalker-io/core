<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Integrate;

use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Test\Integrate\Controller\Stub\StubController;

/**
 * The IntegrateTest class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Property instance.
	 *
	 * @var StubController
	 */
	protected $instance;

	/**
	 * setUp
	 *
	 * @return  void
	 */
	public function setUp()
	{
		$this->instance = new StubController;

		$this->instance->setPackage(new IntegratePackage);
	}

	public function testNoPackage()
	{
		$controller = new StubController;

		$this->assertTrue($controller->getPackage() instanceof NullPackage);
	}
}
