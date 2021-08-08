<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Provider;

use Symfony\Component\Console\Application;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ErrorHandleringProvider class.
 *
 * @since  4.0.0-beta1
 */
class ErrorHandlingProvider implements ServiceProviderInterface, BootableProviderInterface
{
    use IniSetterTrait;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * ErrorHandlingProvider constructor.
     *
     * @param  Config                $config
     * @param  ApplicationInterface  $app
     */
    public function __construct(Config $config, protected ApplicationInterface $app)
    {
        $this->config = $config->extract('error');
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        $iniValues = $this->config->get('ini') ?? [];

        $this->setINIValues($iniValues, $container);

        $error = $container->get(ErrorService::class);

        switch ($this->app->getClient()) {
            case ApplicationInterface::CLIENT_WEB:
            default:
                $error->register(
                    $this->config->get('restore') ?? true,
                    $this->config->get('report_level') ?? E_ALL | E_STRICT,
                    $this->config->get('register_shutdown') ?? true
                );
                break;

            case ApplicationInterface::CLIENT_CONSOLE:
                // Console do not restore exception handler, let console app handle it.
                $error->registerErrors($this->config->get('restore') ?? true);
                $error->registerShutdown();
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(ErrorService::class, function (ErrorService $error, Container $container) {
            foreach ($this->config->getDeep('handlers.' . $this->app->getClient()) ?? [] as $key => $handler) {
                $handler = $container->resolve($handler);

                $error->addHandler($handler, is_numeric($key) ? null : $key);
            }

            return $error;
        });
    }
}
