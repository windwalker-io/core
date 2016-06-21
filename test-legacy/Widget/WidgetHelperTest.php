<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Widget;

use Windwalker\Core\Widget\WidgetManager;

/**
 * Test class of WidgetHelper
 *
 * @since 2.1.1
 */
class WidgetHelperTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Method to test render().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Widget\WidgetHelper::render
	 */
	public function testRender()
	{
		$this->assertEquals('<h1>Test</h1>', WidgetManager::render('test.test'));
	}
}
