<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Facade\Stub;

use Windwalker\Core\Facade\Facade;

/**
 * The StubMvcFacade class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class StubMvcFacade extends Facade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'package.mvc';
}
