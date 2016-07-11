<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Config\ConfigStructure;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Uri\UriData;

/**
 * The AssetProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class AssetProvider implements ServiceProviderInterface
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
		$container->prepareSharedObject(AssetManager::class);
		$container->prepareSharedObject(ScriptManager::class);

		AbstractScript::$instance = function () use ($container)
		{
		    return $container->get(ScriptManager::class);
		};
	}
}
