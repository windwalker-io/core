<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Windwalker\DI\Container;

/**
 * The CliServerFactory class.
 */
class CliServerEngineFactory
{
    public array $serverTypes = [];

    public function __construct(protected Container $container)
    {
    }

    public function create(string $name): CliServerEngineInterface
    {
        return match ($name) {
            'php' => $this->container->newInstance(PhpCliServerEngine::class),
            'swoole' => $this->container->newInstance(SwooleCliServerEngine::class),
            default => $this->container->resolve($this->serverTypes[$name]),
        };
    }

    public function addServerType(string $name, mixed $serverFactory): static
    {
        $this->serverTypes[$name] = $serverFactory;

        return $this;
    }
}
