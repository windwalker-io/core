<?php

declare(strict_types=1);

namespace Windwalker\Core\View;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;

/**
 * The BaseVM class.
 */
#[ViewModel]
class BaseVM implements ViewModelInterface
{
    /**
     * @inheritDoc
     */
    public function prepare(AppContext $app, View $view): mixed
    {
        return [];
    }
}
