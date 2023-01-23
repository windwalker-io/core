<?php

/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Utilities;

use DomainException;
use ErrorException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Server\Connection;

use function count;

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
    protected string $host;

    /**
     * Property connection.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Dumper constructor.
     *
     * @param  string|null  $host
     */
    public function __construct(?string $host = null)
    {
        if (!class_exists(Connection::class)) {
            throw new DomainException('Please install symfony/var-dumper ^4.1 first.');
        }

        $this->host = $host ?: env('VAR_DUMP_SERVER_HOST') ?: 'tcp://127.0.0.1:9912';

        $this->connection = new Connection($this->host);
    }

    /**
     * dump
     *
     * @param  mixed  ...$args
     *
     * @return  void
     *
     * @since  3.4.6
     */
    public function dump(mixed ...$args): void
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
     * @param  array  ...$args
     *
     * @return  bool
     *
     * @throws ErrorException
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
     * @param  array  $args
     *
     * @return  array|mixed
     *
     * @since  3.4.6
     */
    protected function handleValues(array $args): mixed
    {
        if (count($args) === 1) {
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
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Method to set property connection
     *
     * @param  Connection  $connection
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.4.6
     */
    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }
}
