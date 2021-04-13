<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\View;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\State\AppState;

/**
 * Interface ViewModelInterface
 */
interface ViewModelInterface
{
    /**
     * Prepare
     *
     * @param  AppState    $state
     * @param  AppContext  $app
     *
     * @return  array
     */
    public function prepare(AppState $state, AppContext $app): array;
}
