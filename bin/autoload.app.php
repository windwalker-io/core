<?php

declare(strict_types=1);

/**
 * Locates and loads the package or host application's Composer autoloader.
 * For development scripts and tests only; production applications should
 * load their host application's Composer autoloader directly.
 *
 * @internal
 */
$autoloadFiles = [
    dirname(__DIR__) . '/vendor/autoload.php',
    // The package checkout is under <host-app>/vendor/windwalker/core.
    dirname(__DIR__, 4) . '/vendor/autoload.php',
];

foreach ($autoloadFiles as $autoloadFile) {
    if (is_file($autoloadFile)) {
        return require $autoloadFile;
    }
}

return null;
