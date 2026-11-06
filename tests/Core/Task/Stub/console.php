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

$loader = require realpath(getenv('WINDWALKER_TEST_APP_ROOT') ?: dirname(__DIR__, 4)) . '/vendor/autoload.php';
// Test classes (closures, services) are serialized into this child process, so it needs the same test namespace as bootstrap.app.php.
$loader->addPsr4('Windwalker\Tests\\', dirname(__DIR__, 3) . '/');

$app = require __DIR__ . '/application.php';
$transport = \Windwalker\Utilities\Env::get('TASK_TEST_CACHE')
    ? require __DIR__ . '/transport.php'
    : new \Windwalker\Core\Task\CallbackTaskResultTransport(
        static function (): never {
            throw new \LogicException('Test transport is not configured.');
        },
        static fn(): ?array => null,
        static function (): void {}
    );
$app->getContainer()->share(\Windwalker\Core\Task\TaskResultTransportInterface::class, $transport);
$app->getContainer()->prepareSharedObject(TaskPayload::class);
$app->getContainer()->prepareSharedObject(TaskRunCommand::class);
$handler = $app->getContainer()->get(TaskRunCommand::class);
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
