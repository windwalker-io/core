<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Runtime;

use Windwalker\DI\Parameters;
use Windwalker\Filesystem\Path;

/**
 * The Config class.
 */
class Config extends Parameters
{
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
            return $this->get($path);
        }

        [$base, $path] = explode('/', $path, 2);

        $base = $this->get($base);

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
}
