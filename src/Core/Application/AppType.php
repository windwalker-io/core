<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

enum AppType
{
    case WEB;
    case CLI_WEB;
    case CONSOLE;
}
