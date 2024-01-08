<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Traits;

trait FilterAwareViewModelTrait
{
    public function isFiltered(array $filter = [], string $searchText = ''): bool
    {
        if ($searchText !== '') {
            return true;
        }

        foreach ($filter as $value) {
            if ($value !== null) {
                if (is_array($value) && $value !== []) {
                    return true;
                } elseif ((string) $value !== '') {
                    return true;
                }
            }
        }

        return false;
    }
}
