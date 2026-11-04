<?php

/**
 * Isolated console entry for subprocess task protocol tests, without a host app.
 * Remove when TaskRunCommand subprocess tests are removed.
 */

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IO;
use Windwalker\Core\Task\Command\TaskRunCommand;
use Windwalker\Core\Task\TaskPayload;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

$app = require __DIR__ . '/application.php';
$handler = new TaskRunCommand(new TaskPayload($app), $app);
$command = new Command('task:run');
$handler->configure($command);
$command->setCode(
    static fn(InputInterface $input, OutputInterface $output): int => $handler->execute(
        new IO($input, $output, $command)
    )
);

$application = new Application();
$application->addCommand($command);
$application->run();
