<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Facade\Stub;

use Windwalker\Core\Facade\Facade;

/**
 * The StubConfigFacade class.
 * 
 * @since  2.1.1
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
