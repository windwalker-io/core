<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Contract;

interface SortableViewModelInterface
{
    /**
     * Is reorder enabled.
     *
     * @param  string  $ordering
     *
     * @return  bool
     */
    public function reorderEnabled(string $ordering): bool;
}
