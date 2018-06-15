<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace PHPSTORM_META {

    $STATIC_METHOD_TYPES = [
        \Windwalker\DI\Container::get('') => [
            // System
            'application' instanceof \Windwalker\Core\Application\WindwalkerApplicationInterface,
            'app' instanceof \Windwalker\Core\Application\WindwalkerApplicationInterface,
            'package.resolver' instanceof \Windwalker\Core\Package\PackageResolver,
            'config' instanceof \Windwalker\Structure\Structure,

            // Web
            'input' instanceof \Windwalker\IO\Input,
            'environment' instanceof \Windwalker\Environment\WebEnvironment,
            'browser' instanceof \Windwalker\Environment\Browser\Browser,
            'platform' instanceof \Windwalker\Environment\Platform,
            'uri' instanceof \Windwalker\Uri\UriData,

            // Error
            'error.handler' instanceof \Windwalker\Core\Error\ErrorManager,

            // Logger
            'logger' instanceof \Windwalker\Core\Logger\LoggerManager,

            // Event
            'dispatcher' instanceof \Windwalker\Core\Event\EventDispatcher,

            // Database
            'database' instanceof \Windwalker\Database\Driver\AbstractDatabaseDriver,
            'db' instanceof \Windwalker\Database\Driver\AbstractDatabaseDriver,
            'sql.exporter' instanceof \Windwalker\Core\Database\Exporter\AbstractExporter,

            // Router
            'router' instanceof \Windwalker\Core\Router\MainRouter,

            // Language
            'language' instanceof \Windwalker\Core\Language\CoreLanguage,

            // Renderer
            'renderer.manager' instanceof \Windwalker\Core\Renderer\RendererManager,
            'renderer' instanceof \Windwalker\Core\Renderer\RendererManager,
            'package.finder' instanceof \Windwalker\Core\Renderer\Finder\PackageFinder,
            'widget.manager' instanceof \Windwalker\Core\Widget\WidgetManager,

            // Cache
            'cache.manager' instanceof \Windwalker\Core\Cache\CacheManager,
            'cache' instanceof \Windwalker\Cache\CacheInterface,

            // Session
            'session' instanceof \Windwalker\Session\Session,

            // User
            'authentication' instanceof \Windwalker\Authentication\Authentication,
            'authorisation' instanceof \Windwalker\Authorisation\Authorisation,
            'user.manager' instanceof \Windwalker\Core\User\UserManager,
            'user.handler' instanceof \Windwalker\Core\User\UserHandlerInterface,

            // Security
            'security.csrf' instanceof \Windwalker\Core\Security\CsrfGuard,
            'crypt' instanceof \Windwalker\Crypt\Crypt,
            'hasher' instanceof \Windwalker\Crypt\Password,

            // DateTime
            'datetime' instanceof \Windwalker\Core\DateTime\Chronos,

            // Asset
            'asset' instanceof \Windwalker\Core\Asset\AssetManager,
            'script.manager' instanceof \Windwalker\Core\Asset\ScriptManager,

            // Mailer
            'mailer' instanceof \Windwalker\Core\Mailer\MailerManager,

            // Queue
            'queue' instanceof \Windwalker\Core\Queue\Queue,
            'queue.manager' instanceof \Windwalker\Core\Queue\QueueManager,
            'queue.failer' instanceof \Windwalker\Core\Queue\Failer\QueueFailerInterface,
        ],

        new \Windwalker\DI\Container() => [
            // System
            'application' instanceof \Windwalker\Core\Application\WindwalkerApplicationInterface,
            'app' instanceof \Windwalker\Core\Application\WindwalkerApplicationInterface,
            'package.resolver' instanceof \Windwalker\Core\Package\PackageResolver,
            'config' instanceof \Windwalker\Structure\Structure,

            // Web
            'input' instanceof \Windwalker\IO\Input,
            'environment' instanceof \Windwalker\Environment\WebEnvironment,
            'browser' instanceof \Windwalker\Environment\Browser\Browser,
            'platform' instanceof \Windwalker\Environment\Platform,
            'uri' instanceof \Windwalker\Uri\UriData,

            // Error
            'error.handler' instanceof \Windwalker\Core\Error\ErrorManager,

            // Logger
            'logger' instanceof \Windwalker\Core\Logger\LoggerManager,

            // Event
            'dispatcher' instanceof \Windwalker\Core\Event\EventDispatcher,

            // Database
            'database' instanceof \Windwalker\Database\Driver\AbstractDatabaseDriver,
            'db' instanceof \Windwalker\Database\Driver\AbstractDatabaseDriver,
            'sql.exporter' instanceof \Windwalker\Core\Database\Exporter\AbstractExporter,

            // Router
            'router' instanceof \Windwalker\Core\Router\MainRouter,

            // Language
            'language' instanceof \Windwalker\Core\Language\CoreLanguage,

            // Renderer
            'renderer.manager' instanceof \Windwalker\Core\Renderer\RendererManager,
            'renderer' instanceof \Windwalker\Core\Renderer\RendererManager,
            'package.finder' instanceof \Windwalker\Core\Renderer\Finder\PackageFinder,
            'widget.manager' instanceof \Windwalker\Core\Widget\WidgetManager,

            // Cache
            'cache.manager' instanceof \Windwalker\Core\Cache\CacheManager,
            'cache' instanceof \Windwalker\Cache\CacheInterface,

            // Session
            'session' instanceof \Windwalker\Session\Session,

            // User
            'authentication' instanceof \Windwalker\Authentication\Authentication,
            'authorisation' instanceof \Windwalker\Authorisation\Authorisation,
            'user.manager' instanceof \Windwalker\Core\User\UserManager,
            'user.handler' instanceof \Windwalker\Core\User\UserHandlerInterface,

            // Security
            'security.csrf' instanceof \Windwalker\Core\Security\CsrfGuard,
            'crypt' instanceof \Windwalker\Crypt\Crypt,
            'hasher' instanceof \Windwalker\Crypt\Password,

            // DateTime
            'datetime' instanceof \Windwalker\Core\DateTime\Chronos,

            // Asset
            'asset' instanceof \Windwalker\Core\Asset\AssetManager,
            'script.manager' instanceof \Windwalker\Core\Asset\ScriptManager,

            // Mailer
            'mailer' instanceof \Windwalker\Core\Mailer\MailerManager,

            // Queue
            'queue' instanceof \Windwalker\Core\Queue\Queue,
            'queue.manager' instanceof \Windwalker\Core\Queue\QueueManager,
            'queue.failer' instanceof \Windwalker\Core\Queue\Failer\QueueFailerInterface,
        ]
    ];
}
