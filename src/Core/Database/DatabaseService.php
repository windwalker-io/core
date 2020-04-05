<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Database;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Database\Exporter\AbstractExporter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Database\Driver\Mysql\MysqlDriver;
use Windwalker\DI\Container;
use Windwalker\Structure\Structure;

/**
 * The DatabaseService class.
 *
 * @since  3.5
 */
class DatabaseService
{
    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * DatabaseService constructor.
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
     * getDefaultConnectionName
     *
     * @return  string
     *
     * @since  3.5.13
     */
    public function getDefaultConnectionName(): string
    {
        $config = $this->config->extract('database');

        return $config->get('default', 'local');
    }

    /**
     * getDbConfig
     *
     * @param string|null $connection
     *
     * @return  Structure
     *
     * @since  3.5
     */
    public function getDbConfig(?string $connection = null): Structure
    {
        $config = $this->config->extract('database');

        // Check is new or legacy
        if ($config->get('connections')) {
            $connection = $connection ?: $this->getDefaultConnectionName();
            $config     = $config->extract('connections.' . $connection);
        }

        return $config;
    }

    /**
     * getConnection
     *
     * @param string|null $connection
     * @param array       $options
     *
     * @return  AbstractDatabaseDriver
     *
     * @since  3.5
     */
    public function getConnection(?string $connection = 'local', array $options = []): AbstractDatabaseDriver
    {
        $key = 'connection.' . $connection;

        if (!$this->container->has($key)) {
            $this->container->share($key, $this->createConnection($connection, $options));
        }

        return $this->container->get($key);
    }

    /**
     * getConnection
     *
     * @param string|null $connection
     * @param array       $options
     *
     * @return  AbstractDatabaseDriver
     *
     * @since  3.5
     */
    public function createConnection(?string $connection = null, array $options = []): AbstractDatabaseDriver
    {
        $config = $this->config->extract('database');

        // Check is new or legacy
        if ($config->get('connections')) {
            $connection = $connection ?: $config->get('default', 'local');
            $config     = $config->extract('connections.' . $connection);
        }

        $options = array_merge(
            [
                'driver' => $config->get('driver', 'mysql'),
                'host' => $config->get('host', 'localhost'),
                'user' => $config->get('user', 'root'),
                'password' => $config->get('password', ''),
                'database' => $config->get('name'),
                'charset' => $config->get('charset'),
                'prefix' => $config->get('prefix', 'wind_'),
            ],
            $options
        );

        $db = DatabaseFactory::getDbo($options['driver'], $options, true);

        $db->setDebug($this->config->get('system.debug', false));

        if ($db instanceof MysqlDriver && $config->get('strict', true)) {
            $this->strictMode($db);
        }

        if ($db instanceof MysqlDriver && $names = $config->get('set_names', 'utf8mb4')) {
            $db->connect()->getConnection()->exec('SET NAMES ' . $names);
        }

        return $db;
    }

    /**
     * getExporter
     *
     * @param string|null $connection
     *
     * @return  AbstractExporter
     *
     * @since  3.5
     */
    public function getExporter(?string $connection = null): AbstractExporter
    {
        $config = $this->config->extract('database');

        // Check is new or legacy
        if ($config->get('connections')) {
            $connection = $connection ?: $config->get('connection', 'local');

            $config = $config->extract('connections.' . $connection);
        }

        $driver = $config->get('driver', 'mysql');

        $class = 'Windwalker\Core\Database\Exporter\\' . ucfirst($driver) . 'Exporter';

        return $this->container->createSharedObject($class);
    }

    /**
     * strictMode
     *
     * @param MysqlDriver $db
     *
     * @return  void
     */
    public function strictMode(MysqlDriver $db): void
    {
        // Set Mysql to strict mode
        $modes = [
            // 'ONLY_FULL_GROUP_BY',
            'STRICT_TRANS_TABLES',
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_AUTO_CREATE_USER',
            'NO_ENGINE_SUBSTITUTION',
            'NO_ZERO_DATE',
            'NO_ZERO_IN_DATE',
        ];

        try {
            $db->connect()
                ->getConnection()
                ->exec("SET @@SESSION.sql_mode = '" . implode(',', $modes) . "';");
        } catch (\RuntimeException $e) {
            // If not success, hide error.
        }
    }
}
