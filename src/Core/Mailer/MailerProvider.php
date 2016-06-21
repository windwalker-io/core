<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The MailerProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MailerProvider implements ServiceProviderInterface
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
		$closure = function (Container $container)
		{
		    return new MailerManager;
		};

		$container->share(MailerManager::class, $closure)
			->alias('mailer', MailerManager::class);
	}
}
