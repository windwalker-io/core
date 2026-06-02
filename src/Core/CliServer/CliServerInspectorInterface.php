<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

interface CliServerInspectorInterface
{
    public function shouldEnableConnectionPool(): bool;
}
