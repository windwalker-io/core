<?php

declare(strict_types=1);

namespace Windwalker\Debugger\Module\Ajax;

use RuntimeException;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Controller;
use Windwalker\Core\Attributes\JsonApi;
use Windwalker\Debugger\Module\Dashboard\DashboardRepository;
use Windwalker\DI\Attributes\Autowire;

use function Windwalker\collect;

/**
 * The AjaxController class.
 */
#[Controller]
class AjaxController
{
    #[JsonApi]
    public function history(#[Autowire] DashboardRepository $repository): array
    {
        return $repository->getItems(100, false);
    }

    #[JsonApi]
    public function last(#[Autowire] DashboardRepository $repository): string
    {
        return $repository->getLastItem()['id'];
    }

    #[JsonApi]
    public function data(#[Autowire] DashboardRepository $repository, AppContext $app): mixed
    {
        $id = $app->input('id');
        $path = $app->input('path');

        if (is_string($path)) {
            return $repository->getDataByPath((string) $id, $path);
        }

        if (is_array($path)) {
            $result = [];

            foreach ($path as $k => $p) {
                $result[$k] = $repository->getDataByPath((string) $id, $p);
            }

            return $result;
        }

        throw new RuntimeException('Wrong path format.');
    }
}
