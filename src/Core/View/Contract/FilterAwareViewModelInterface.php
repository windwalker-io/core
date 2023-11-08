<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Contract;

interface FilterAwareViewModelInterface
{
    public function isFiltered(array $filters = [], string $searchText = ''): bool;
}
