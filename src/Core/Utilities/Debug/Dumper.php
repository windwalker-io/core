<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Utilities\Debug;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Server\Connection;
use Windwalker\Core\Ioc;

/**
 * The Dumper class.
 *
 * @since  3.4.6
 */
class Dumper
{
    /**
     * Property host.
     *
     * @var  string
     */
    protected $host;

    /**
     * Property connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Dumper constructor.
     *
     * @param string $host
     */
    public function __construct(string $host = null)
    {
        if (!class_exists(Connection::class)) {
            throw new \DomainException('Please install symfony/var-dumper ^4.1 first.');
        }

        $this->host = $host ?: Ioc::getConfig()->get('system.ver_dump_server_host', 'tcp://127.0.0.1:9912');

        $this->connection = new Connection($this->host);
    }

    /**
     * dump
     *
     * @param mixed ...$args
     *
     * @return  void
     *
     * @since  3.4.6
     */
    public function dump(...$args)
    {
        $values = $this->handleValues($args);

        $data = (new VarCloner())->cloneVar($values);

        if ($this->connection->write($data) === false) {
            dump($values);
        }
    }

    /**
     * dumpToServer
     *
     * @param array ...$args
     *
     * @return  bool
     *
     * @since  3.4.6
     */
    public function dumpToServer(...$args): bool
    {
        $values = $this->handleValues($args);

        $data = (new VarCloner())->cloneVar($values);

        return $this->connection->write($data);
    }

    /**
     * handleValues
     *
     * @param array $args
     *
     * @return  array|mixed
     *
     * @since  3.4.6
     */
    protected function handleValues(array $args)
    {
        if (\count($args) === 1) {
            $values = $args[0];
        } else {
            $values = [];

            foreach ($args as $i => $arg) {
                $values['Value ' . ($i + 1)] = $arg;
            }
        }

        return $values;
    }

    /**
     * Method to get property Connection
     *
     * @return  Connection
     *
     * @since  3.4.6
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Method to set property connection
     *
     * @param   Connection $connection
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.4.6
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }
}
