<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The RequestProvider class.
 */
class RequestProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            Config::class,
            $container->getParameters()
        );
    }
}
