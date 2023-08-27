<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Windwalker\Data\ValueObject;

/**
 * The CliServerStateData class.
 */
class CliServerState extends ValueObject
{
    public string $name = '';
    public int $pid = 0;
    public string $host = '';
    public int $port = 0;
    public int $managerPid = 0;
    public array $managerOptions = [];

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setPid(int $pid): static
    {
        $this->pid = $pid;

        return $this;
    }

    public function setManagerPid(int $managerPid): static
    {
        $this->managerPid = $managerPid;

        return $this;
    }

    public function setManagerOptions(array $managerOptions): static
    {
        $this->managerOptions = $managerOptions;

        return $this;
    }

    public function getManagerPid(): int
    {
        return $this->managerPid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManagerOptions(): array
    {
        return $this->managerOptions;
    }
}
