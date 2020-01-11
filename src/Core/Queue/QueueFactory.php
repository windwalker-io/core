<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Queue\Driver\QueueDriverInterface;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Queue;
use Windwalker\Structure\Structure;

/**
 * The QueueFactory class.
 *
 * @see    QueueManager
 *
 * @method static Queue                 create($connection = null)
 * @method static Queue                 getManager($connection = null)
 * @method static QueueDriverInterface  createDriverByConnection($connection = null)
 * @method static QueueDriverInterface  getDriver($driver = null, array $config = [])
 * @method static QueueDriverInterface  createDriver($driver, array $config = [])
 * @method static QueueFailerInterface  createFailer($driver = null)
 * @method static string                getConnectionName()
 * @method static string                getDriverName($conn = null)
 * @method static Structure             getConnectionConfig($conn = null)
 *
 * @since  3.2
 */
class QueueFactory extends AbstractProxyFacade
{
    /**
     * Property _key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'queue.manager';
    // phpcs:enable
}
