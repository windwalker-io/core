<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

enum MigrationDirection
{
    case UP;
    case DOWN;
}
