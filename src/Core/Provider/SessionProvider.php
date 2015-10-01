<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Session\Database\WindwalkerAdapter;
use Windwalker\Session\Session;
use Windwalker\Utilities\ArrayHelper;

/**
 * The SessionProvider class.
 * 
 * @since  2.0
 */
class SessionProvider implements ServiceProviderInterface
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
		$self = $this;

		$closure = function(Container $container) use ($self)
		{
			/** @var \Windwalker\Registry\Registry $config */
			$config = $container->get('system.config');
			$uri = $container->get('system.uri');

			$handler  = $config->get('session.handler', 'native');
			$options  = (array) $config->get('session', array());

			$options['cookie_path'] = !empty($options['cookie_path']) ? $options['cookie_path'] : $uri->get('base.path');

			$sesion = new Session($self->getHandler($handler, $container, $options), null, null, null, $options);

			return $sesion;
		};

		$container->share('system.session', $closure)
			->alias('session', 'system.session');
	}

	/**
	 * getHandler
	 *
	 * @param string    $handler
	 * @param Container $container
	 *
	 * @return \Windwalker\Session\Handler\HandlerInterface
	 */
	public function getHandler($handler, Container $container, $options)
	{
		$class = sprintf('Windwalker\Session\Handler\%sHandler', ucfirst($handler));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Session handler: %s not supported', $class));
		}

		if ($handler == 'database')
		{
			$adapter = new WindwalkerAdapter($container->get('system.database'), ArrayHelper::getValue($options, 'database', array()));

			return new $class($adapter);
		}

		return new $class;
	}
}
