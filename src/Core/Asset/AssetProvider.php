<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AssetProvider class.
 */
class AssetProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(AssetService::class);
    }
}
