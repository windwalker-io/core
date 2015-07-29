<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
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
