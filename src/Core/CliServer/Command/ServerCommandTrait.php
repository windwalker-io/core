<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Command;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\IOInterface;
use Windwalker\Core\CliServer\Contracts\CliServerEngineInterface;

/**
 * Trait ServerCommandTrait
 */
trait ServerCommandTrait
{
    protected function createEngine(
        string $engine,
        string $name,
        IOInterface $io
    ): CliServerEngineInterface {
        return $this->serverFactory->createEngine(
            $engine,
            $name,
            [
                'state_file' => $this->app->path("@temp/servers/{$engine}-{$name}-state.json")
            ],
            $io->getOutput()
        );
    }

    protected function invalidEngine(IOInterface $io, mixed $engineName): int
    {
        $io->errorStyle()->warning('Invalid server engine: ' . $engineName);

        return Command::FAILURE;
    }

    protected function getServerMainFiles(string $engine): array
    {
        $registryFile = WINDWALKER_RESOURCES . '/registry/servers.php';

        if (!is_file($registryFile)) {
            return [];
        }

        $registry = include $registryFile;

        return $registry[$engine] ?? [];
    }

    /**
     * @param  IOInterface  $io
     * @param  string       $engine
     * @param  string       $name
     *
     * @return string
     */
    protected function getMainFile(IOInterface $io, string $engine, string $name): string
    {
        $main = (string) $io->getOption('main');

        if (!is_file($main)) {
            $servers = $this->getServerMainFiles($engine);
            $main = $servers[$name] ?? '';
        }

        return $main;
    }

    protected function mustGetMainFile(IOInterface $io, string $engine, string $name): string
    {
        $main = $this->getMainFile($io, $engine, $name);

        if (!$main || !is_file($main)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Main file not found for engine: %s - name: %s',
                    $engine,
                    $name
                )
            );
        }

        return $main;
    }
}
