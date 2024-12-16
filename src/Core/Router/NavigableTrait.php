<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Windwalker\DI\Attributes\Inject;

trait NavigableTrait
{
    #[Inject]
    protected Navigator $nav;

    public function getNav(): Navigator
    {
        return $this->nav;
    }
}
