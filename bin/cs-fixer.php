#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpCsFixer\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

$packageRoot = dirname(__DIR__);
$autoload = require __DIR__ . '/autoload.app.php';

if ($autoload === null) {
    fwrite(STDERR, 'Could not find a Composer autoload for the package or host app.' . PHP_EOL);

    exit(1);
}

if (!class_exists(Application::class)) {
    fwrite(STDERR, 'PHP-CS-Fixer is not available from the discovered Composer autoload.' . PHP_EOL);

    exit(1);
}

if (!chdir($packageRoot)) {
    fwrite(STDERR, 'Could not change working directory to ' . $packageRoot . PHP_EOL);

    exit(1);
}

$input = new ArgvInput(
    array_merge(['php-cs-fixer'], array_slice($argv, 1))
);
$application = new Application();

exit($application->run($input));
