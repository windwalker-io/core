<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ErrorHandleringProvider class.
 *
 * @since  __DEPLOY_VERSION__
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
     * @param  Config  $config
     */
    public function __construct(Config $config)
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
        $error->register(
            $this->config->get('restore') ?? true,
            $this->config->get('report_level') ?? E_ALL | E_STRICT,
            $this->config->get('register_shutdown') ?? true
        );
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(ErrorService::class, function (ErrorService $error, Container $container) {
            foreach ($this->config->getDeep('factories.handlers') ?? [] as $key => $handler) {
                if (is_string($handler)) {
                    $handler = $container->resolve($handler);
                }

                $error->addHandler($handler, is_numeric($key) ? null : $key);
            }

            return $error;
        });
    }
}
