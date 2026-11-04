<?php

/**
 * Isolated application with a public test-only key for task protocol tests.
 * Remove when the task payload and subprocess tests are removed.
 */

declare(strict_types=1);

use Windwalker\Core\Testing\Stub\ApplicationTraitStub;
use Windwalker\Core\Application\ApplicationInterface;

$app = new ApplicationTraitStub();
$app->getContainer()->getParameters()->set('app', ['secret' => 'task-protocol-test-key-not-for-production']);
$app->getContainer()->share(ApplicationInterface::class, $app);
$app->getContainer()->prepareSharedObject(\Windwalker\Tests\Core\Task\Stub\RuntimeTaskService::class);

return $app;
