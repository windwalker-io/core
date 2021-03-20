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

/**
 * The ViewModel class.
 */
abstract class AbstractViewModel implements ViewModelInterface
{
    /**
     * Prepare
     *
     * @param  \Windwalker\Data\Collection  $state
     * @param  AppContext                   $app
     *
     * @return  array
     */
    abstract public function prepare(\Windwalker\Data\Collection $state, AppContext $app): array;
}
