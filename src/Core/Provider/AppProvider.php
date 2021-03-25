<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageInterface;

/**
 * The AppProvider class.
 *
 * @since  __DEPLOY_VERSION__
 */
class AppProvider implements ServiceProviderInterface
{
    protected ApplicationInterface $app;

    /**
     * AppProvider constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritDoc
     * @throws DefinitionException
     */
    public function register(Container $container): void
    {
        $container->share(Config::class, $container->getParameters());
        $container->share(Container::class, $container);
        $container->share(get_class($this->app), $this->app);
        $container->share(ApplicationInterface::class, $this->app);
        $container->prepareSharedObject(PathResolver::class);

        // Language
        $this->prepareLanguage($container);
    }

    /**
     * prepareLanguage
     *
     * @param  Container  $container
     *
     * @return  void
     */
    public function prepareLanguage(Container $container): void
    {

    }
}
