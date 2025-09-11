<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Windwalker\DI\Attributes\Inject;

trait ChronosAwareTrait
{
    #[Inject]
    protected ChronosService $chronos;
}
