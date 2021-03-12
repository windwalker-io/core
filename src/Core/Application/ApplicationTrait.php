<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Windwalker\Core\Console\Process\ProcessRunnerTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceAwareTrait;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Filesystem\Path;

/**
 * Trait ApplicationTrait
 *
 * @property-read Config $config
 */
trait ApplicationTrait
{
    use ServiceAwareTrait {
        resolve as diResolve;
    }
    use EventAwareTrait;
    use ProcessRunnerTrait;

    /**
     * config
     *
     * @param  string       $name
     * @param  string|null  $delimiter
     *
     * @return  mixed
     */
    public function config(string $name, ?string $delimiter = '.'): mixed
    {
        return $this->getContainer()->getParam($name, $delimiter);
    }

    /**
     * Get system path.
     *
     * Example: $app->path(`@root/path/to/file`);
     *
     * @param  string  $path
     *
     * @return  string
     */
    public function path(string $path): string
    {
        if (str_contains($path, '\\')) {
            $path = str_replace('\\', '/', $path);
        }

        if (!str_starts_with($path, '@')) {
            return Path::normalize($path);
        }

        if (!str_contains($path, '/')) {
            return $this->config($path);
        }

        [$base, $path] = explode('/', $path);

        $base = $this->config($base);

        if (!$base) {
            throw new \LogicException(
                sprintf(
                    'The system path: %s not exists in config.',
                    $base
                )
            );
        }

        return Path::normalize($base . '/' . $path);
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * loadConfig
     *
     * @param  mixed        $source
     * @param  string|null  $format
     * @param  array        $options
     *
     * @return  void
     */
    public function loadConfig(mixed $source, ?string $format = null, array $options = []): void
    {
        $this->getContainer()->loadParameters($source, $format, $options);
    }

    public function __get(string $name)
    {
        if ($name === 'config') {
            return $this->getContainer()->getParameters();
        }

        if ($name === 'container') {
            return $this->getContainer();
        }

        throw new \OutOfRangeException('No such property: ' . $name . ' in ' . static::class);
    }
}
