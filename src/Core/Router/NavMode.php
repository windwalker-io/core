<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

enum NavMode
{
    case RAW;
    case PATH;
    case FULL;
}
