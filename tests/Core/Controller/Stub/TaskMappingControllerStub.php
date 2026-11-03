<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller\Stub;

use Windwalker\Core\Attributes\TaskMapping;

/**
 * Controller with a `#[TaskMapping]` attribute, used to exercise
 * `ControllerDispatcher::processTaskMapping()`.
 */
#[TaskMapping(tasks: ['index' => 'home'], methods: ['PUT' => 'update'])]
class TaskMappingControllerStub
{
    public static ?string $lastCalled = null;

    public function home(): string
    {
        return self::$lastCalled = 'home-called';
    }

    public function update(): string
    {
        return self::$lastCalled = 'update-called';
    }
}
