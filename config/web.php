<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
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
			'error'    => \Windwalker\Core\Error\ErrorHandlingProvider::class,
			'logger'   => \Windwalker\Core\Provider\LoggerProvider::class,
			'event'    => \Windwalker\Core\Provider\EventProvider::class,
			'database' => \Windwalker\Core\Provider\DatabaseProvider::class,
			'router'   => \Windwalker\Core\Provider\RouterProvider::class,
			'lang'     => \Windwalker\Core\Provider\LanguageProvider::class,
			'template' => \Windwalker\Core\Provider\RendererProvider::class,
			'cache'    => \Windwalker\Core\Provider\CacheProvider::class,
			'session'  => \Windwalker\Core\Provider\SessionProvider::class,
			'auth'     => \Windwalker\Core\Provider\UserProvider::class,
			'security' => \Windwalker\Core\Provider\SecurityProvider::class,
			'datetime' => \Windwalker\Core\Provider\DateTimeProvider::class,
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

		],
		
		'di' => [
			'aliases' => [
				// System
				'application' => \Windwalker\Core\Application\WindwalkerApplicationInterface::class,
				'app'         => \Windwalker\Core\Application\WindwalkerApplicationInterface::class,
				'package.resolver' => \Windwalker\Core\Package\PackageResolver::class,
				
				// Web
				'input'       => \Windwalker\IO\Input::class,
				'environment' => \Windwalker\Environment\WebEnvironment::class,
				'browser'     => \Windwalker\Environment\Browser\Browser::class,
				'platform'    => \Windwalker\Environment\Platform::class,
				'uri'         => \Windwalker\Uri\UriData::class,
				
				// Error
				'error.handler' => \Windwalker\Core\Error\ErrorManager::class,
				
				// Logger
				'logger' => \Windwalker\Core\Logger\LoggerManager::class,
				
				// Event
				'dispatcher' => \Windwalker\Core\Event\EventDispatcher::class,
				
				// Database
				'database'     => \Windwalker\Database\Driver\AbstractDatabaseDriver::class,
				'db'           => \Windwalker\Database\Driver\AbstractDatabaseDriver::class,
				'sql.exporter' => \Windwalker\Core\Database\Exporter\AbstractExporter::class,
				
				// Router
				'router' => \Windwalker\Core\Router\CoreRouter::class,
				
				// Language
				'language' => \Windwalker\Core\Language\CoreLanguage::class,
				
				// Renderer
				'renderer.manager' => \Windwalker\Core\Renderer\RendererManager::class,
				'renderer'         => \Windwalker\Core\Renderer\RendererManager::class,
				'package.finder'   => \Windwalker\Core\Renderer\Finder\PackageFinder::class,
				'widget.manager'   => \Windwalker\Core\Widget\WidgetManager::class,
				
				// Cache
				'cache.factory' => \Windwalker\Core\Cache\CacheFactory::class,
				
				// Session
				'session' => \Windwalker\Session\Session::class,
				
				// User
				'authentication' => \Windwalker\Authentication\Authentication::class,
				'authorisation' => \Windwalker\Authorisation\Authorisation::class,
				'user.manager' => \Windwalker\Core\User\UserManager::class,
				
				// CSRF
				'security.csrf' => \Windwalker\Core\Security\CsrfGuard::class,
				
				// DateTime
				'datetime' => \Windwalker\Core\DateTime\DateTime::class,
				
				// Asset
				'asset' => \Windwalker\Core\Asset\AssetManager::class,
				'script.manager' => \Windwalker\Core\Asset\ScriptManager::class
			]
		]
	]
);
