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
use Windwalker\Core\Queue\Failer\QueueFailerInterface;
use Windwalker\Structure\Structure;

/**
 * The QueueFactory class.
 *
 * @see QueueManager
 *
 * @method  Queue                 create($connection = null)
 * @method  Queue                 getManager($connection = null)
 * @method  QueueDriverInterface  createDriverByConnection($connection = null)
 * @method  QueueDriverInterface  getDriver($driver = null, array $config = [])
 * @method  QueueDriverInterface  createDriver($driver, array $config = [])
 * @method  QueueFailerInterface  createFailer($driver = null)
 * @method  string                getConnectionName()
 * @method  string                getDriverName($conn = null)
 * @method  Structure             getConnectionConfig($conn = null)
 *
 * @since  3.2
 */
class QueueFactory extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'queue.manager';
}
