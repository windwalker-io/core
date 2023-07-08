<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

enum AppType
{
    case WEB;
    case CLI_WEB;
    case CONSOLE;
}
