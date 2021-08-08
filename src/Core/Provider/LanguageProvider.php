<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Language\LangService;
use Windwalker\Core\Language\TranslatorWrapper;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageInterface;

/**
 * The LanguageProvider class.
 */
class LanguageProvider implements ServiceProviderInterface, BootableDeferredProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(LangService::class)
            ->alias(Language::class, LangService::class)
            ->alias(LanguageInterface::class, Language::class)
            ->extend(
                LangService::class,
                function (LangService $langService) {
                    return $langService->loadAllFromPath(__DIR__ . '/../../../resources/languages', 'php');
                }
            );

        $container->prepareSharedObject(TranslatorWrapper::class);
    }

    /**
     * @inheritDoc
     */
    public function bootDeferred(Container $container): void
    {
        // todo: move to after request start
        $container->get(LangService::class)->loadAll();
    }
}
