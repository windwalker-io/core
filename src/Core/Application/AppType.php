<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

enum AppType
{
    case WEB;
    case CLI_WEB;
    case CONSOLE;

    public function isWeb(): bool
    {
        return $this === self::WEB;
    }

    public function isCliWeb(): bool
    {
        return $this === self::CLI_WEB;
    }

    public function isConsole(): bool
    {
        return $this === self::CONSOLE;
    }
}
