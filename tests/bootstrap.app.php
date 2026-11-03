<?php

/**
 * PHPUnit bootstrap for running Core tests inside a host application.
 *
 * Referenced by phpunit.app.dist.xml. It loads the host's autoloader and
 * etc/define.php, boots Runtime, and prepares TestApp::$instance and
 * TestApp::$sharedCtx (also assigned to TestApp::$ctx), which the traits in
 * src/Core/Testing/Traits rely on by default.
 *
 * The host root is taken from the WINDWALKER_TEST_APP_ROOT environment
 * variable, otherwise from the conventional vendor/windwalker/core location.
 */

declare(strict_types=1);

use Windwalker\Tests\TestApp;

$init = include __DIR__ . '/../src/Core/Testing/test.init.php';

$container = $init(
    root: $root = env('WINDWALKER_TEST_APP_ROOT') ?: dirname(__DIR__),
    workDir: $root
);

TestApp::createInstance(
    container: $container,
    config: [
        '@root' => $root,
        '@temp' => $root . '/tmp',
        '@public' => $root . '/tests/public',
        '@migrations' => $root . '/tests/resources/migrations',
        '@seeders' => $root . '/tests/resources/seeders',
    ]
);
