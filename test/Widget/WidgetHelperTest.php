<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Widget;

use Windwalker\Core\Widget\WidgetHelper;

/**
 * Test class of WidgetHelper
 *
 * @since {DEPLOY_VERSION}
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
		$this->assertEquals('<h1>Test</h1>', WidgetHelper::render('test.test'));
	}
}
