<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\View;

use Windwalker\Utilities\Wrapper\WrapperInterface;

/**
 * The CollapseWrapper class.
 */
class CollapseWrapper implements WrapperInterface
{
    public array $args;

    /**
     * CollapseWrapper constructor.
     *
     * @param  array  $args
     */
    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public function __invoke(mixed $src = null): array
    {
        return array_merge(...$this->args);
    }
}
