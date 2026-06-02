<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer\PhpNative;

use Windwalker\Core\CliServer\CliServerInspectorInterface;

class TestInspector implements CliServerInspectorInterface
{
    public bool $enableConnectionPool = false;

    public function shouldEnableConnectionPool(): bool
    {
        return $this->enableConnectionPool;
    }
}
