<?php

declare(strict_types=1);

namespace Windwalker\Debugger\Module\Index;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;

/**
 * The IndexView class.
 */
#[ViewModel(layout: 'index')]
class IndexView implements ViewModelInterface
{
    public function prepare(AppContext $app, View $view): array
    {
        return [];
    }
}
