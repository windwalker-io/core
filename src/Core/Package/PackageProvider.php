<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\Core\Mvc\ControllerResolver;
use Windwalker\Core\Mvc\ModelResolver;
use Windwalker\Core\Mvc\MvcResolver;
use Windwalker\Core\Mvc\ViewResolver;
use Windwalker\Core\Router\CoreRoute;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The PackageProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class PackageProvider implements ServiceProviderInterface
{
	use PackageAwareTrait;

	/**
	 * PackageProvider constructor.
	 *
	 * @param AbstractPackage $package
	 */
	public function __construct(AbstractPackage $package)
	{
		$this->package = $package;
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
		$container->share('controller.resolver', function(Container $container)
		{
			return new ControllerResolver($this->package, $container);
		});

		$container->share('model.resolver', function(Container $container)
		{
			return new ModelResolver($this->package, $container);
		});

		$container->share('view.resolver', function(Container $container)
		{
			return new ViewResolver($this->package, $container);
		});

		$container->share('mvc.resolver', function(Container $container)
		{
			return new MvcResolver(
				$container->get('controller.resolver'),
				$container->get('model.resolver'),
				$container->get('view.resolver')
			);
		});

		if ($this->package->app->isWeb())
		{
			// Router
			$container->share('route', function (Container $container)
			{
				return new CoreRoute($container->get('router'), $this->package);
			});
		}
	}
}
