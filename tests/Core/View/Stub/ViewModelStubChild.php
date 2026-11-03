<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub;

/**
 * A subclass of ViewModelStub, used only to exercise
 * View::addParentViewPath(), which needs a view-model with a real parent class.
 */
class ViewModelStubChild extends ViewModelStub {}
