<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

/**
 * Invokable listener used to exercise ApplicationTrait::handleListener()'s
 * `[ClassName, '__invoke']` array-listener branch: after resolving the class
 * name into an instance, `is_callable([$instance, '__invoke'])` becomes true,
 * so the recursive call registers it via `$dispatcher->on()`.
 */
class InvokableListenerStub
{
    public function __invoke(mixed $event = null): void
    {
        //
    }
}
