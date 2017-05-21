<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

/**
 * The SqsQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class SqsQueueDriver extends AbstractQueueDriver
{
	/**
	 * Property client.
	 *
	 * @var
	 */
	protected $client;
}
