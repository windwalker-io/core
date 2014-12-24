<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test;

use Windwalker\Filesystem\Path;

/**
 * The AbstractBaseTestCase class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractBaseTestCase extends \PHPUnit_Framework_TestCase
{
	/**
	 * assertPathEquals
	 *
	 * @param string $path1
	 * @param string $path2
	 * @param string $msg
	 *
	 * @return  void
	 */
	protected function assertPathEquals($path1, $path2, $msg = null)
	{
		$this->assertEquals(
			Path::clean($path1),
			Path::clean($path2),
			$msg
		);
	}
}
