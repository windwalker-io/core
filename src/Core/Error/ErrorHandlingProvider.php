<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Logger\LoggerManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ErrorHandlingProvider class.
 *
 * @since  3.0
 */
class ErrorHandlingProvider implements ServiceProviderInterface
{
    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * ErrorHandlingProvider constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * boot
     *
     * @param Container $container
     *
     * @return  void
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function boot(Container $container)
    {
        error_reporting($this->config->get('system.error_reporting', 0));

        /** @var ErrorManager $handler */
        $handler = $container->get(ErrorManager::class);

        $handler->setErrorTemplate(
            $this->config->get('error.template', 'windwalker.error.default'),
            $this->config->get('error.engine', 'php')
        );

        $handler->register(true, null, true);
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(ErrorManager::class, function (ErrorManager $error, Container $container) {
            foreach ((array) $this->config->get('error.handlers', []) as $key => $handler) {
                if (is_string($handler)) {
                    $handler = $container->newInstance($handler);
                }

                $error->addHandler($handler, is_numeric($key) ? null : $key);
            }

            return $error;
        });

        $container->extend(LoggerManager::class, function (LoggerManager $manager, Container $container) {
            if ($container->get('config')->get('error.log', false)) {
                $logger = $manager->createRotatingLogger('error', LogLevel::ERROR);
            } else {
                $logger = new NullLogger;
            }

            $manager->addLogger('error', $logger);

            return $manager;
        });
    }
}
