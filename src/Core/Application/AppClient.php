<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

enum AppClient
{
    case WEB;
    case CONSOLE;

    public function isWeb(): bool
    {
        return $this === self::WEB;
    }

    public function isConsole(): bool
    {
        return $this === self::CONSOLE;
    }
}
