<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Windwalker\Data\ValueObject;

/**
 * The CliServerStateData class.
 */
class CliServerState extends ValueObject
{
    public string $name = '';
    public string $serverName = '';
    public int $verbosity = 0;
    public int $masterPid = 0;
    public int $managerPid = 0;
    public string $host = '';
    public int $port = 0;
    public int $workerNumber = 0;
    public array $startupOptions = [];
    public array $server = [];
    public array $subServers = [];

    /**
     * @codeCoverageIgnore
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setMasterPid(int $masterPid): static
    {
        $this->masterPid = $masterPid;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setManagerPid(int $managerPid): static
    {
        $this->managerPid = $managerPid;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setStartupOptions(array $startupOptions): static
    {
        $this->startupOptions = $startupOptions;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getManagerPid(): int
    {
        return $this->managerPid;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getMasterPid(): int
    {
        return $this->masterPid;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getStartupOptions(): array
    {
        return $this->startupOptions;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSubServers(): array
    {
        return $this->subServers;
    }

    /**
     * @param  array  $subServers
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setSubServers(array $subServers): static
    {
        $this->subServers = $subServers;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * @param  array  $server
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setServer(array $server): static
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getWorkerNumber(): int
    {
        return $this->workerNumber;
    }

    /**
     * @param  int  $workerNumber
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setWorkerNumber(int $workerNumber): static
    {
        $this->workerNumber = $workerNumber;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getServerName(): string
    {
        return $this->serverName;
    }

    /**
     * @param  string  $serverName
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setServerName(string $serverName): static
    {
        $this->serverName = $serverName;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    /**
     * @param  int  $verbosity
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setVerbosity(int $verbosity): static
    {
        $this->verbosity = $verbosity;

        return $this;
    }
}
