<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Mvc;

use Windwalker\Core\Test\AbstractBaseTestCase;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;
use Windwalker\Core\Test\Mvc\Controller\Stub\StubController;
use Windwalker\Filesystem\Path;

/**
 * The IntegrateTest class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class ControllerTest extends AbstractBaseTestCase
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

		$this->instance->setPackage(new MvcPackage);
	}

	/**
	 * getController
	 *
	 * @param AbstractPackage $package
	 *
	 * @return  StubController
	 */
	protected function getController(AbstractPackage $package = null)
	{
		if ($package)
		{
			return new StubController(null, null, null, $package);
		}

		return new StubController;
	}

	/**
	 * getPackagePath
	 *
	 * @param AbstractPackage $package
	 *
	 * @return  string
	 */
	protected function getPath($package)
	{
		$ref = new \ReflectionClass($package);

		return dirname($ref->getFileName());
	}

	/**
	 * testNoPackage
	 *
	 * @return  void
	 */
	public function testNoPackage()
	{
		$controller = new StubController;

		$this->assertTrue($controller->getPackage() instanceof NullPackage);

		$config = $this->instance->getConfig();

		$this->assertEquals('stub', $config['name']);
		$this->assertEquals('mvc', $config['package.name']);
		$this->assertEquals($this->getPath($this->instance->getPackage()), Path::clean($config['package.path']));
	}

	/**
	 * testWithPackage
	 *
	 * @return  void
	 */
	public function testWithPackage()
	{
		$this->assertTrue($this->instance->getPackage() instanceof MvcPackage);

		$config = $this->instance->getConfig();

		$this->assertEquals('stub', $config['name']);
		$this->assertEquals('mvc', $config['package.name']);
		$this->assertEquals($this->getPath($this->instance->getPackage()), Path::clean($config['package.path']));
	}
}
