<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Session\Bag\FlashBag;
use Windwalker\Session\Bag\FlashBagInterface;
use Windwalker\Session\Bag\SessionBag;
use Windwalker\Session\Bag\SessionBagInterface;
use Windwalker\Session\Bridge\NativeBridge;
use Windwalker\Session\Bridge\SessionBridgeInterface;
use Windwalker\Session\Database\AbstractDatabaseAdapter;
use Windwalker\Session\Database\WindwalkerAdapter;
use Windwalker\Session\Handler\HandlerInterface;
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
		$container->share(HandlerInterface::class, [$this, 'handler']);
		$container->share(AbstractDatabaseAdapter::class, [$this, 'dbAdapter']);

		$container->bind(SessionBagInterface::class,    SessionBag::class);
		$container->bind(FlashBagInterface::class,      FlashBag::class);
		$container->bind(SessionBridgeInterface::class, NativeBridge::class);

		$closure = function(Container $container)
		{
			/** @var \Windwalker\Structure\Structure $config */
			$config = $container->get('config');
			$uri = $container->get('uri');

			$options  = (array) $config->get('session', array());

			$options['cookie_path'] = !empty($options['cookie_path']) ? $options['cookie_path'] : $uri->path;
			$options['cookie_domain'] = parse_url($uri->host, PHP_URL_HOST);

			return $container->newInstance(Session::class, ['options' => $options]);
		};

		$container->share(Session::class, $closure);
	}

	/**
	 * getHandler
	 * @param Container $container
	 *
	 * @return \Windwalker\Session\Handler\HandlerInterface
	 */
	public function handler(Container $container)
	{
		$config = $container->get('config');
		$handler = $config->get('session.handler', 'native');

		return $container->newInstance(sprintf('Windwalker\Session\Handler\%sHandler', ucfirst($handler)));
	}

	/**
	 * dbAdapter
	 *
	 * @param Container $container
	 *
	 * @return  WindwalkerAdapter
	 */
	public function dbAdapter(Container $container)
	{
		$config = $container->get('config');
		$options = $config->get('session', []);

		return new WindwalkerAdapter($container->get('database'), ArrayHelper::getValue($options, 'database', array()));
	}
}
