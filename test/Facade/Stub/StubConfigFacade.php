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
 * The StubConfigFacade class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class StubConfigFacade extends Facade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'mvc.config';

	/**
	 * Property group.
	 *
	 * @var string
	 */
	protected static $name = 'mvc';
}
