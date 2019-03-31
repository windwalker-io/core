<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Pheanstalk\Pheanstalk;
use Windwalker\Core\Config\Config;
use Windwalker\DI\Container;
use Windwalker\Queue\Driver\BeanstalkdQueueDriver;
use Windwalker\Queue\Driver\DatabaseQueueDriver;
use Windwalker\Queue\Driver\IronmqQueueDriver;
use Windwalker\Queue\Driver\NullQueueDriver;
use Windwalker\Queue\Driver\PdoQueueDriver;
use Windwalker\Queue\Driver\QueueDriverInterface;
use Windwalker\Queue\Driver\RabbitmqQueueDriver;
use Windwalker\Queue\Driver\ResqueQueueDriver;
use Windwalker\Queue\Driver\SqsQueueDriver;
use Windwalker\Queue\Driver\SyncQueueDriver;
use Windwalker\Queue\Failer\DatabaseQueueFailer;
use Windwalker\Queue\Failer\NullQueueFailer;
use Windwalker\Queue\Failer\PdoQueueFailer;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Queue;
use Windwalker\Structure\Structure;

/**
 * The QueueDriverFactory class.
 *
 * @since  3.2
 */
class QueueManager
{
    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * Property managers.
     *
     * @var  Queue[]
     */
    protected $managers = [];

    /**
     * Property drivers.
     *
     * @var  QueueDriverInterface[]
     */
    protected $drivers = [];

    /**
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * QueueDriverFactory constructor.
     *
     * @param Config    $config
     * @param Container $container
     */
    public function __construct(Config $config, Container $container)
    {
        $this->config    = $config;
        $this->container = $container;
    }

    /**
     * create
     *
     * @param $connection $driver
     *
     * @return  Queue
     */
    public function create($connection = null)
    {
        $driver = null;

        if ($connection !== false) {
            $driver = $this->createDriverByConnection($connection);
        }

        return new Queue($driver);
    }

    /**
     * getManager
     *
     * @param string $connection
     *
     * @return  Queue
     */
    public function getManager($connection = null)
    {
        $connection = $connection ? strtolower($connection) : 'default';

        if (!isset($this->managers[$connection])) {
            $this->managers[$connection] = $this->create($connection);
        }

        return $this->managers[$connection];
    }

    /**
     * createDriverByConnection
     *
     * @param string $connection
     *
     * @return  QueueDriverInterface
     */
    public function createDriverByConnection($connection = null)
    {
        $connection = $connection ?: $this->getConnectionName();

        $config = $this->getConnectionConfig($connection);

        return $this->createDriver($config->get('driver', 'sync'), $config->toArray());
    }

    /**
     * getDriver
     *
     * @param string $driver
     * @param array  $config
     *
     * @return QueueDriverInterface
     */
    public function getDriver($driver = null, array $config = [])
    {
        $driver = strtolower($driver);

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver, $config);
        }

        return $this->drivers[$driver];
    }

    /**
     * create
     *
     * @param string $driver
     * @param array  $config
     *
     * @return QueueDriverInterface
     */
    public function createDriver($driver, array $config = [])
    {
        $driver = strtolower($driver);

        $queueConfig = new Structure($config);

        if (!$queueConfig->toArray()) {
            throw new \LogicException('No queue config for ' . $driver);
        }

        // TODO: All driver should have DI pattern.
        switch ($driver) {
            case 'sqs':
                return new SqsQueueDriver(
                    $queueConfig->get('key'),
                    $queueConfig->get('secret'),
                    $queueConfig->get('queue', 'default'),
                    [
                        'region' => $queueConfig->get('region', 'us-west-2'),
                        'version' => $queueConfig->get('version', 'latest'),
                    ]
                );

            case 'sync':
                return new SyncQueueDriver();

            case 'database':
                return new DatabaseQueueDriver(
                    $this->container->get('db'),
                    $queueConfig->get('queue', 'default'),
                    $queueConfig->get('table', 'queue_jobs')
                );

            case 'pdo':
                return new PdoQueueDriver(
                    $this->container->get('db')->getConnection(),
                    $queueConfig->get('queue', 'default'),
                    $queueConfig->get('table', 'queue_jobs')
                );

            case 'ironmq':
                return new IronmqQueueDriver(
                    $queueConfig->get('project_id'),
                    $queueConfig->get('token'),
                    $queueConfig->get('queue', 'default'),
                    [
                        'host' => $queueConfig->get('region', 'mq-aws-us-east-1-1') . '.iron.io',
                    ]
                );

            case 'rabbitmq':
                return new RabbitmqQueueDriver(
                    $queueConfig->get('queue', 'default'),
                    (array) $queueConfig->toArray()
                );

            case 'resque':
                return new ResqueQueueDriver(
                    $queueConfig->get('host', 'localhost'),
                    $queueConfig->get('port', '6379'),
                    $queueConfig->get('queue', 'default')
                );

            case 'beanstalkd':
                return new BeanstalkdQueueDriver(
                    $queueConfig->get('host', '127.0.0.1'),
                    $queueConfig->get('queue', 'default'),
                    $queueConfig->get('timeout', 60)
                );

            default:
                return new NullQueueDriver();
        }
    }

    /**
     * createFailer
     *
     * @param string $driver
     *
     * @return  QueueFailerInterface
     * @throws \UnexpectedValueException
     */
    public function createFailer($driver = null)
    {
        $driver = $driver ?: $this->config->get('queue.failer.driver', 'database');

        switch ($driver) {
            case 'database':
                $failer = new DatabaseQueueFailer(
                    $this->container->get('db'),
                    $this->config->get('queue.failer.table')
                );

                if (!$failer->isSupported()) {
                    return new NullQueueFailer();
                }

                return $failer;
                break;
            case 'pdo':
                $failer = new PdoQueueFailer(
                    $this->container->get('db')->getConnection(),
                    $this->config->get('queue.failer.table')
                );

                if ($failer->isSupported()) {
                    return new NullQueueFailer();
                }

                return $failer;
                break;
            case 'null':
            default:
                return new NullQueueFailer();
        }
    }

    /**
     * getConnectionName
     *
     * @return  string
     */
    public function getConnectionName()
    {
        return $this->config->get('queue.connection', 'sync');
    }

    /**
     * getDriverName
     *
     * @return  string
     */
    public function getDriverName($conn = null)
    {
        $conn = $conn ?: $this->getConnectionName();

        return $this->config->get('queue.' . $conn . '.driver', 'sync');
    }

    /**
     * getConnectionConfig
     *
     * @param   string $conn
     *
     * @return  Structure
     */
    public function getConnectionConfig($conn = null)
    {
        $conn = $conn ?: $this->getConnectionName();

        return $this->config->extract('queue.' . $conn);
    }
}
