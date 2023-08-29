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
use Windwalker\Core\CliServer\PhpNative\PhpNativeEngine;
use Windwalker\Core\CliServer\Swoole\SwooleEngine;
use Windwalker\DI\Container;

/**
 * The CliServerFactory class.
 */
class CliServerFactory
{
    public array $engineTypes = [];

    public function __construct(protected Container $container)
    {
    }

    public function createEngine(
        string $engine,
        string $name,
        array $options = [],
        ?ConsoleOutputInterface $output = null
    ): CliServerEngineInterface {
        $output ??= new ConsoleOutput();

        $args = [
            ConsoleOutputInterface::class => $output,
            'output' => $output,
            'name' => $name,
            'options' => $options
        ];

        return match ($engine) {
            'php' => $this->container->newInstance(PhpNativeEngine::class, $args),
            'swoole' => $this->container->newInstance(SwooleEngine::class, $args),
            default => $this->container->resolve($this->engineTypes[$engine], $args),
        };
    }

    public function addEngineType(string $name, mixed $factory): static
    {
        $this->engineTypes[$name] = $factory;

        return $this;
    }
}
