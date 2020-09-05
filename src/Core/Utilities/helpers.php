<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker {

    if (!function_exists('load_configs')) {
        function load_configs(string $path, bool $groupWithFilename = false): array
        {
            $config = [];

            foreach (glob($path) as $file) {
                if ($groupWithFilename) {
                    $name = pathinfo($file, PATHINFO_FILENAME);

                    $config[][$name] = include $file;
                } else {
                    $config[] = include $file;
                }
            }

            return array_merge(...$config);
        }
    }
}
