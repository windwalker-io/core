<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\View\View;

/**
 * The DefaultController class.
 */
#[\Windwalker\Core\Attributes\Controller]
class DefaultController
{
    public function index(string $view, AppContext $app): mixed
    {
        /** @var View $vm */
        $vm = $app->make($view);

        return $vm->render();
    }
}
