<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware\Enum;

enum WwwRedirect: int
{
    case NO_ACTIONS = 0;

    case WWW = 1;

    case NON_WWW = 2;
}
