<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

use Windwalker\Utilities\ArrayHelper;

return ArrayHelper::merge(
	include __DIR__ . '/windwalker.php',
	[
		'packages' => [

		],

		'providers' =>[
			'web'      => \Windwalker\Core\Provider\WebProvider::class,
			'datetime' => \Windwalker\Core\Provider\DateTimeProvider::class,
			'error'    => \Windwalker\Core\Error\ErrorHandlingProvider::class,
			'logger'   => \Windwalker\Core\Provider\LoggerProvider::class,
			'event'    => \Windwalker\Core\Provider\EventProvider::class,
			'database' => \Windwalker\Core\Provider\DatabaseProvider::class,
			'router'   => \Windwalker\Core\Provider\RouterProvider::class,
			'lang'     => \Windwalker\Core\Provider\LanguageProvider::class,
			'renderer' => \Windwalker\Core\Provider\RendererProvider::class,
			'cache'    => \Windwalker\Core\Provider\CacheProvider::class,
			'session'  => \Windwalker\Core\Provider\SessionProvider::class,
			'auth'     => \Windwalker\Core\Provider\UserProvider::class,
			'security' => \Windwalker\Core\Provider\SecurityProvider::class,
			'asset'    => \Windwalker\Core\Asset\AssetProvider::class
		],

		'routing' => [
			'files' => [
				
			]
		],

		'middlewares' => [
			900  => \Windwalker\Core\Application\Middleware\SessionRaiseMiddleware::class,
			800  => \Windwalker\Core\Application\Middleware\RoutingMiddleware::class,
		],

		'configs' => [
		],

		'listeners' => [

		]
	]
);
