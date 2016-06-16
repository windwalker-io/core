<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Facade\Stub;

use Windwalker\Core\Proxy\AbstractFacade;

/**
 * The StubMvcFacade class.
 * 
 * @since  2.1.1
 */
abstract class StubMvcFacade extends AbstractFacade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'package.mvc';
}
