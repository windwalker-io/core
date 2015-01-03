<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Renderer;

use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Core\Utilities\Iterator\PriorityQueue;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Queue\Priority;

/**
 * Test class of RendererHelper
 *
 * @since {DEPLOY_VERSION}
 */
class RendererHelperTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Method to test getRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getRenderer
	 */
	public function testGetRenderer()
	{
		$this->assertInstanceOf('Windwalker\Renderer\PhpRenderer', RendererHelper::getRenderer());
		$this->assertInstanceOf('Windwalker\Renderer\PhpRenderer', RendererHelper::getRenderer('php'));
		$this->assertInstanceOf('Windwalker\Renderer\BladeRenderer', RendererHelper::getRenderer('blade'));
		$this->assertInstanceOf('Windwalker\Renderer\TwigRenderer', RendererHelper::getRenderer('twig'));
		$this->assertInstanceOf('Windwalker\Renderer\MustacheRenderer', RendererHelper::getRenderer('mustache'));

		$this->assertEquals(RendererHelper::getGlobalPaths(), RendererHelper::getRenderer()->getPaths());
	}

	/**
	 * Method to test getPhpRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getPhpRenderer
	 */
	public function testGetPhpRenderer()
	{
		$this->assertInstanceOf('Windwalker\Renderer\PhpRenderer', RendererHelper::getPhpRenderer());
	}

	/**
	 * Method to test getBladeRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getBladeRenderer
	 */
	public function testGetBladeRenderer()
	{
		$this->assertInstanceOf('Windwalker\Renderer\BladeRenderer', RendererHelper::getBladeRenderer());
	}

	/**
	 * Method to test getTwigRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getTwigRenderer
	 */
	public function testGetTwigRenderer()
	{
		$this->assertInstanceOf('Windwalker\Renderer\TwigRenderer', RendererHelper::getTwigRenderer());
	}

	/**
	 * Method to test getMustacheRenderer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getMustacheRenderer
	 */
	public function testGetMustacheRenderer()
	{
		$this->assertInstanceOf('Windwalker\Renderer\MustacheRenderer', RendererHelper::getMustacheRenderer());
	}

	/**
	 * Method to test getGlobalPaths().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getGlobalPaths
	 */
	public function testGetGlobalPaths()
	{
		$paths = RendererHelper::getGlobalPaths();

		$this->assertTrue($paths instanceof PriorityQueue);

		$array = array(
			Path::clean(WINDWALKER_TEMPLATES),
			realpath(WINDWALKER_SOURCE . '/../src/Core/Resources/Templates')
		);

		$this->assertEquals($array, $paths->toArray());
	}

	/**
	 * Method to test addGlobalPath().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::addGlobalPath
	 * @covers Windwalker\Core\Renderer\RendererHelper::addPath
	 */
	public function testAddGlobalPath()
	{
		RendererHelper::addGlobalPath(WINDWALKER_CACHE, Priority::HIGH);

		$paths = RendererHelper::getGlobalPaths();

		$this->assertEquals(WINDWALKER_CACHE, $paths->current());
	}

	/**
	 * Method to test reset().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::reset
	 */
	public function testReset()
	{
		RendererHelper::addGlobalPath(WINDWALKER_TEMP, Priority::MAX);

		$paths = RendererHelper::getGlobalPaths();

		$this->assertEquals(WINDWALKER_TEMP, $paths->current());

		RendererHelper::reset();

		$paths = RendererHelper::getGlobalPaths();

		$this->assertNotEquals(WINDWALKER_TEMP, $paths->current());
	}
}
