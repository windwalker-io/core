<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub\Module\Front\Home;

use Windwalker\Tests\Core\View\Stub\ViewModelStub;

/**
 * A view-model placed under a `...\Module\...` namespace segment, used only
 * to exercise View::guessName()'s `Module\` fallback branch when the
 * view-model's namespace does not start with the configured namespace root.
 */
class DummyViewModel extends ViewModelStub {}
