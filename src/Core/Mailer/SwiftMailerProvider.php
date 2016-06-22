<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Mailer\Adapter\SwiftMailerAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;

/**
 * The MailerProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class SwiftMailerProvider implements ServiceProviderInterface
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
		$container->share(\Swift_Mailer::class, function (Container $container)
		{
			/** @var Registry $config */
			$config = $container->get('config');

			$transport = SwiftMailerAdapter::createTransport($config->get('mail.transport'), (array) $config->get('mail'));

			return \Swift_Mailer::newInstance($transport);
		})->alias('swiftmailer', \Swift_Mailer::class);

		$container->share(SwiftMailerAdapter::class, function (Container $container)
		{
		    return $container->createSharedObject(SwiftMailerAdapter::class);
		})->alias('mailer.adapter.swiftmailer', SwiftMailerAdapter::class);

		$closure = function(MailerManager $mailer, Container $container)
		{
			if (!class_exists('Swift_Mailer'))
			{
				throw new \LogicException('Please install swiftmailer/swiftmailer 5.* first.');
			}

			$mailer->setAdapter($container->get('mailer.adapter.swiftmailer'));

			return $mailer;
		};

		$container->extend(MailerManager::class, $closure);
	}
}
