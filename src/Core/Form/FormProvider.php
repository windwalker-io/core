<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The FormProvider class.
 */
class FormProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(FormFactory::class);
        $container->prepareSharedObject(FormRenderer::class);
    }
}
