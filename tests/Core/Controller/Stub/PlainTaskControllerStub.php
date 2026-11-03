<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller\Stub;

/**
 * Plain (non-`ControllerInterface`) controller used to exercise
 * `ControllerDispatcher`'s default task mapping, task-name normalization,
 * and "handler is a plain callable" branch.
 *
 * ControllerDispatcher::prepareArrayCallable()'s plain-callable branch does
 * not propagate the called method's return value, so `$lastCalled` is used
 * to assert which method actually ran.
 */
class PlainTaskControllerStub
{
    public static ?string $lastCalled = null;

    public function index(): string
    {
        return self::$lastCalled = 'index-called';
    }

    public function save(): string
    {
        return self::$lastCalled = 'save-called';
    }

    public function someMethod(): string
    {
        return self::$lastCalled = 'some_method-called';
    }

    /**
     * Intentionally non-public so `is_callable([$this, 'secret'])` is false,
     * exercising `ControllerDispatcher::prepareArrayCallable()`'s
     * not-callable branch.
     */
    private function secret(): string
    {
        return 'unreachable';
    }
}
