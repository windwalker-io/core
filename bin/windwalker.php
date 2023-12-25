<?php

declare(strict_types=1);

use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Runtime\Runtime;

Runtime::boot(WINDWALKER_ROOT, __DIR__);
Runtime::loadConfig(Runtime::getRootDir() . '/etc/runtime.php');

$container = Runtime::getContainer();

/** @var ConsoleApplication $console */
$console = $container->resolve('factories.console');

exit($console->run());
