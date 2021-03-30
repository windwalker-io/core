<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Language\LangService;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Filter\FilterFactory;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageInterface;

/**
 * The RuntimeProvider class.
 */
class RuntimeProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $config    = $container->getParameters();
        $container->share(Config::class, $config);
        $container->share(Container::class, $container);

        $this->registerFilters($container);
    }

    protected function registerFilters(Container $container): void
    {
        $container->prepareSharedObject(FilterFactory::class);
        $container->prepareSharedObject(FilterService::class);
    }
}
