<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\WebApplication;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Browser\Browser;
use Windwalker\Environment\Platform;
use Windwalker\Environment\WebEnvironment;
use Windwalker\IO\Input;
use Windwalker\IO\PsrInput;
use Windwalker\Uri\UriData;

/**
 * The WebProvider class.
 * 
 * @since  2.0
 */
class WebProvider implements ServiceProviderInterface
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
		$app = $container->get('application');

		$container->share(WebApplication::class, $app);

		// Input
		$container->share(Input::class, function (Container $container) use ($app)
		{
		    return PsrInput::create($app->getRequest());
		})->alias(PsrInput::class, Input::class);

		// Request
//		$container->share(ServerRequest::class, $app->getRequest())
//			->alias(ServerRequestInterface::class, ServerRequest::class);

		// Environment
		$container->share(WebEnvironment::class, $app->getEnvironment());
		$container->share(Browser::class,        $app->getEnvironment()->getBrowser());
		$container->share(Platform::class,       $app->getEnvironment()->getPlatform());

		// Uri
		$container->share(UriData::class, function (Container $container) use ($app)
		{
			return $app->getServer()->getUriData();
		});
	}
}
