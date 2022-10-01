<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use LogicException;
use Windwalker\Core\Runtime\Config;
use Windwalker\Filesystem\Path;

/**
 * The PathResolver class.
 */
class PathResolver
{
    /**
     * PathResolver constructor.
     *
     * @param  Config  $config
     */
    public function __construct(protected Config $config)
    {
    }

    public function all(): array
    {
        $paths = [];

        foreach ($this->config as $key => $value) {
            if (str_starts_with($key, '@')) {
                $paths[$key] = $value;
            }
        }

        return $paths;
    }

    /**
     * Get system path.
     *
     * Example: $app->path(`@root/path/to/file`);
     *
     * @param  string  $path
     *
     * @return  ?string
     */
    public function resolve(string $path): ?string
    {
        if (!str_starts_with($path, '@')) {
            return $this->addBase($path);
        }

        if (str_contains($path, '\\')) {
            $path = str_replace('\\', '/', $path);
        }

        if (!str_contains($path, '/')) {
            return $this->config->get($path);
        }

        [$base, $path] = explode('/', $path, 2);

        $base = $this->config->get($base);

        if (!$base) {
            throw new LogicException(
                sprintf(
                    'The system path: %s not exists in config.',
                    $base
                )
            );
        }

        return $base . '/' . $path;
    }

    public function addBase(string $path, string $base = '@root'): string
    {
        // Absolute
        if (static::isAbsolute($path)) {
            return $path;
        }

        if (str_starts_with($base, '@')) {
            $base = $this->resolve($base);
        }

        return $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function isAbsolute(string $path): bool
    {
        return Path::isAbsolute($path);
    }
}
