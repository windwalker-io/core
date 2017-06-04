<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The CoreQueue class.
 *
 * @since  __DEPLOY_VERSION__
 */
class CoreQueue extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'queue';
}
