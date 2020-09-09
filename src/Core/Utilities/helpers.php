<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {
    if (!function_exists('env')) {
        /**
         * Get ENV var.
         *
         * @param string $name
         * @param mixed  $default
         *
         * @return  string
         *
         * @since  3.3
         */
        function env(string $name, $default = null): ?string
        {
            return $_SERVER[$name] ?? $_ENV[$name] ?? $default;
        }
    }
}

namespace Windwalker {

    if (!function_exists('include_files')) {
        function include_files(string $path, array $contextData = []): array
        {
            $resultBag = [];

            extract($contextData, EXTR_OVERWRITE);
            unset($contextData);

            foreach (glob($path) as $file) {
                $resultBag[] = include $file;
            }

            return array_merge(...$resultBag);
        }
    }
}
