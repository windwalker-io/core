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

        $showFilterBar = false;

        array_walk_recursive(
            $filter,
            static function ($v) use (&$showFilterBar) {
                if ((string) $v !== '') {
                    $showFilterBar = true;
                }
            }
        );

        return $showFilterBar;
    }
}
