<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error;

use Windwalker\Core\Application\WebApplication;
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
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * ErrorHandlingProvider constructor.
	 *
	 * @param WebApplication $app
	 */
	public function __construct(WebApplication $app)
	{
		$this->app = $app;
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
		error_reporting($this->app->get('system.error_reporting', 0));

		/** @var ErrorManager $handler */
		$handler = $container->get('error.handler');
		
		$handler->setErrorTemplate($this->app->get('error.template', 'windwalker.error.default'));
		
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
		$closure = function (Container $container)
		{
			/** @var ErrorManager $error */
			$error = $container->createSharedObject(ErrorManager::class);

			$config = $container->get('config');

			$handlers = (array) $config->get('error.handlers', []);

			foreach ($handlers as $key => $handler)
			{
				if (is_string($handler) && class_exists($handler))
				{
					$handler = new $handler($this->app);
				}

				$error->addHandler($handler, is_numeric($key) ? $key : null);
			}

			return $error;
		};

		$container->share(ErrorManager::class, $closure);
	}
}
