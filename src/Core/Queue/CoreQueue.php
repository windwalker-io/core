<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Queue\Driver\QueueDriverInterface;
use Windwalker\Queue\QueueMessage;

/**
 * The CoreQueue class.
 *
 * @see    Queue
 *
 * @method static int           push($job, $delay = 0, $queue = null, array $options = [])
 * @method static int           pushRaw($body, $delay = 0, $queue = null, array $options = [])
 * @method static QueueMessage  pop($queue = null)
 * @method static void          delete($message)
 * @method static void          release($message, $delay = 0)
 * @method static QueueMessage  getMessageByJob($job, array $data = [])
 * @method static QueueDriverInterface  getDriver()
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
