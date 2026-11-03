<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewPrepare;
use Windwalker\Core\View\View;

/**
 * A plain object (no ViewModelInterface, no prepare() method) that exposes
 * its "prepare" behavior through a #[ViewPrepare]-attributed method instead,
 * used by ViewTest to exercise View::findPrepareMethod()'s attribute-based
 * fallback branch.
 */
class AttributeBasedViewModelStub
{
    public mixed $prepareReturn = '<p>FromAttribute</p>';

    #[ViewPrepare]
    public function run(AppContext $app, View $view): mixed
    {
        return $this->prepareReturn;
    }
}
