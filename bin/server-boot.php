<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Runtime\Runtime;

return static function (string $engine, array $argv) {
    CliServerRuntime::boot($engine, $argv);

    // Runtime::ipBlock(['dev'], env('DEV_ALLOW_IPS'));

    Runtime::boot(WINDWALKER_ROOT, __DIR__);

    Runtime::loadConfig(Runtime::getRootDir() . '/etc/runtime.php');

    return Runtime::getContainer();
};
