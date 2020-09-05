<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

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
