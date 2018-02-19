<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Language\CoreLanguage;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageInterface;

/**
 * The LanguageProvider class.
 *
 * @since  2.0
 */
class LanguageProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(CoreLanguage::class)
            ->bindShared(Language::class, CoreLanguage::class)
            ->bindShared(LanguageInterface::class, CoreLanguage::class);
    }
}
