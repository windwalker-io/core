<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Runtime\Runtime;

return static function (string $root, string $workDir) {
    /** @var ClassLoader $classLoader */
    $classLoader = require $root . '/vendor/autoload.php';

    // Some legacy code (predating PathResolver/ApplicationInterface) still
    // reads this host-owned constant directly; define it here so Core's own
    // test suite can exercise that code without a host app's etc/define.php.
    if (!defined('WINDWALKER_ROOT')) {
        define('WINDWALKER_ROOT', $root);
    }

    Runtime::boot($root, $workDir);

    // Under the CLI, the app type is CLI_WEB, and database boot asks for the CLI server inspector.
    CliServerRuntime::boot('test', []);

    return [Runtime::getContainer(), $classLoader];
};
