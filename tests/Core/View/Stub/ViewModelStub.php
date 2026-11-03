<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;

/**
 * A plain ViewModelInterface double used by ViewTest to exercise
 * View::render()'s prepare/response handling without a real host app.
 * Remove once ViewTest stops needing a configurable prepare() return value.
 */
class ViewModelStub implements ViewModelInterface
{
    public mixed $prepareReturn = [];

    public function prepare(AppContext $app, View $view): mixed
    {
        return $this->prepareReturn;
    }
}
