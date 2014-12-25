<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Renderer;

use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\TwigRenderer;

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
		$this->assertTrue(RendererHelper::getRenderer() instanceof PhpRenderer);

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
		$this->assertTrue(RendererHelper::getPhpRenderer() instanceof PhpRenderer);

		$this->assertEquals(RendererHelper::getGlobalPaths(), RendererHelper::getPhpRenderer()->getPaths());
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
		if (!class_exists('Illuminate\View\Environment'))
		{
			$this->markTestSkipped('Illuminate not installed');
		}

		$this->assertTrue(RendererHelper::getBladeRenderer() instanceof BladeRenderer);

		$this->assertEquals(RendererHelper::getGlobalPaths(), RendererHelper::getBladeRenderer()->getPaths());
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
		$this->assertTrue(RendererHelper::getTwigRenderer() instanceof TwigRenderer);

		$this->assertEquals(RendererHelper::getGlobalPaths(), RendererHelper::getTwigRenderer()->getPaths());
	}

	/**
	 * Method to test getGlobalPaths().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::getGlobalPaths
	 * @TODO   Implement testGetGlobalPaths().
	 */
	public function testGetGlobalPaths()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test addPath().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::addPath
	 * @TODO   Implement testAddPath().
	 */
	public function testAddPath()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test reset().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Renderer\RendererHelper::reset
	 * @TODO   Implement testReset().
	 */
	public function testReset()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
