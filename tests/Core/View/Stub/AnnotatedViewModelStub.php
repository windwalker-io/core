<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewMetadata;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\Utilities\Attributes\Prop;

/**
 * A ViewModelInterface double carrying a #[Prop] property and a
 * #[ViewMetadata]-attributed method, used by ViewTest to exercise
 * View::injectData() and the ViewMetadata attribute loop in
 * View::prepareHtmlFrame().
 */
class AnnotatedViewModelStub implements ViewModelInterface
{
    #[Prop]
    public ?string $name = null;

    public mixed $prepareReturn = [];

    public function prepare(AppContext $app, View $view): mixed
    {
        return $this->prepareReturn;
    }

    #[ViewMetadata]
    public function registerMeta(AppContext $app, View $view): void
    {
        $view->set('metaRegistered', true);
    }
}
