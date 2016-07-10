<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

return [
	'packages' => [

	],

	'providers' =>[
		'logger' => \Windwalker\Core\Provider\LoggerProvider::class,
		'event'  => \Windwalker\Core\Provider\EventProvider::class,
		'mailer' => \Windwalker\Core\Mailer\MailerProvider::class,
		'swiftmailer' => \Windwalker\Core\Mailer\SwiftMailerProvider::class
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
			'authorisation'  => \Windwalker\Authorisation\Authorisation::class,
			'user.manager'   => \Windwalker\Core\User\UserManager::class,

			// CSRF
			'security.csrf' => \Windwalker\Core\Security\CsrfGuard::class,

			// DateTime
			'datetime' => \Windwalker\Core\DateTime\DateTime::class,

			// Asset
			'asset' => \Windwalker\Core\Asset\AssetManager::class,
			'script.manager' => \Windwalker\Core\Asset\ScriptManager::class
		]
	],
	
	'path' => [
		'root'       => null,
		'bin'        => null,
		'cache'      => null,
		'etc'        => null,
		'logs'       => null,
		'resources'  => null,
		'source'     => null,
		'temp'       => null,
		'templates'  => null,
		'vendor'     => null,
		'public'     => null,
		'migrations' => null,
		'seeders'    => null,
		'languages'  => null,
	]
];