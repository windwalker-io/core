<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error;

use Psr\Log\NullLogger;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Logger\LoggerManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ErrorHandlingProvider class.
 *
 * @since  {DEPLOY_VERSION}
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
	 */
	public function boot(Container $container)
	{
		error_reporting($this->config->get('system.error_reporting', 0));

		/** @var ErrorManager $handler */
		$handler = $container->get(ErrorManager::class);
		
		$handler->setErrorTemplate($this->config->get('error.template', 'windwalker.error.default'));
		
		$handler->register(true, null, true);
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		$container->prepareSharedObject(ErrorManager::class, function (ErrorManager $error, Container $container)
		{
			foreach ((array) $this->config->get('error.handlers', []) as $key => $handler)
			{
				if (is_string($handler))
				{
					$handler = $container->newInstance($handler);
				}

				$error->addHandler($handler, is_numeric($key) ? $key : null);
			}

			return $error;
		});

		if (!$container->get('config')->get('error.log', false))
		{
			$container->extend('logger', function (LoggerManager $logger, Container $container)
			{
			    $logger->addLogger('error', new NullLogger);

				return $logger;
			});
		}
	}
}
