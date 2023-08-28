<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Windwalker\Core\CliServer\Contracts\CliServerEngineInterface;
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

    public function create(string $name, ?ConsoleOutputInterface $output = null): CliServerEngineInterface
    {
        $output ??= new ConsoleOutput();

        $args = [
            ConsoleOutputInterface::class => $output,
            'output' => $output
        ];

        return match ($name) {
            'php' => $this->container->newInstance(PhpNativeEngine::class, $args),
            'swoole' => $this->container->newInstance(SwooleEngine::class, $args),
            default => $this->container->resolve($this->serverTypes[$name], $args),
        };
    }

    public function addServerType(string $name, mixed $serverFactory): static
    {
        $this->serverTypes[$name] = $serverFactory;

        return $this;
    }
}
