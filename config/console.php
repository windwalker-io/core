<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

use Windwalker\Utilities\Arr;

return Arr::mergeRecursive(
    include __DIR__ . '/windwalker.php',
    [
        'packages' => [

        ],

        'providers' => [
            'console'  => \Windwalker\Core\Provider\ConsoleProvider::class,
            'logger'   => \Windwalker\Core\Provider\LoggerProvider::class,
            'event'    => \Windwalker\Core\Provider\EventProvider::class,
            'database' => \Windwalker\Core\Provider\DatabaseProvider::class,
            'lang'     => \Windwalker\Core\Provider\LanguageProvider::class,
            'cache'    => \Windwalker\Core\Provider\CacheProvider::class,
            'session'  => \Windwalker\Core\Provider\SessionProvider::class,
            'auth'     => \Windwalker\Core\Provider\UserProvider::class,
            'security' => \Windwalker\Core\Provider\SecurityProvider::class,
            'mailer'   => \Windwalker\Core\Mailer\MailerProvider::class,
            'mailer_adapter' => \Windwalker\Core\Mailer\SwiftMailerProvider::class,
            'queue'    => \Windwalker\Core\Queue\QueueProvider::class
        ],

        'console' => [
            'commands' => [
                'asset'     => \Windwalker\Core\Asset\Command\AssetCommand::class,
                'migration' => \Windwalker\Core\Migration\Command\MigrationCommand::class,
                'seed'      => \Windwalker\Core\Seeder\Command\SeedCommand::class,
                'package'   => \Windwalker\Core\Package\Command\PackageCommand::class,
                'queue'     => \Windwalker\Core\Queue\Command\QueueCommand::class
            ],
        ],

        'configs' => [
        ],

        'listeners' => [
        ]
    ]
);
