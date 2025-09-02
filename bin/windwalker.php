<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Runtime\Runtime;

Runtime::boot(WINDWALKER_ROOT, __DIR__);

$file = Runtime::getRootDir() . '/etc/runtime.config.php';

if (!is_file($file)) {
    $file = Runtime::getRootDir() . '/etc/runtime.config.php';
}

Runtime::loadConfig($file);

$container = Runtime::getContainer();

try {
    /** @var ConsoleApplication $console */
    $console = $container->resolve('factories.console');
} catch (\Throwable $e) {
    $app = new Application();
    $app->renderThrowable($e, new ConsoleOutput());
    exit(255);
}

$console->setCatchErrors(true);

exit($console->run());
