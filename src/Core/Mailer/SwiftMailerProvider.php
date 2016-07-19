<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Mailer\Adapter\MailerAdapterInterface;
use Windwalker\Core\Mailer\Adapter\SwiftMailerAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

/**
 * The MailerProvider class.
 *
 * @since  3.0
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
		$container->share(\Swift_Mailer::class, [$this, 'swiftmailer'])
			->alias('swiftmailer', \Swift_Mailer::class);

		$container->share(SwiftMailerAdapter::class, function (Container $container)
		{
		    return $container->newInstance(SwiftMailerAdapter::class);
		})->alias('mailer.adapter.swiftmailer', SwiftMailerAdapter::class)
			->alias(MailerAdapterInterface::class, SwiftMailerAdapter::class);
	}

	/**
	 * swiftmailer
	 *
	 * @param Container $container
	 *
	 * @return  \Swift_Mailer
	 */
	public function swiftmailer(Container $container)
	{
		if (!class_exists('Swift_Mailer'))
		{
			throw new \LogicException('Please install swiftmailer/swiftmailer 5.* first.');
		}
		
		/** @var Structure $config */
		$config = $container->get('config');

		$transport = SwiftMailerAdapter::createTransport($config->get('mail.transport'), (array) $config->get('mail'));

		return \Swift_Mailer::newInstance($transport);
	}
}
