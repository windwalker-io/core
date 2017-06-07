<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Queue\Driver\QueueDriverInterface;

/**
 * The CoreQueue class.
 *
 * @see  Queue
 *
 * @method  int           push($job, $delay = 0, $queue = null, array $options = [])
 * @method  int           pushRaw($body, $delay = 0, $queue = null, array $options = [])
 * @method  QueueMessage  pop($queue = null)
 * @method  void          delete($message)
 * @method  void          release($message, $delay = 0)
 * @method  QueueMessage  getMessageByJob($job, array $data = [])
 * @method  QueueDriverInterface  getDriver()
 *
 * @since  3.2
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
