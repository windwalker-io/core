<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;
use Windwalker\Filesystem\File;

/**
 * The StarterInstaller class.
 *
 * @since  2.1.1
 */
class StarterInstaller
{
    /**
     * Do install.
     *
     * @param Event $event The command event.
     *
     * @return  void
     */
    public static function rootInstall(Event $event)
    {
        include getcwd() . '/vendor/autoload.php';

        $io = $event->getIO();

        static::genSecretCode($io);

        static::genSecretConfig($io);

        // Complete
        $io->write('Install complete.');
    }

    /**
     * Generate secret code.
     *
     * @param IOInterface $io
     *
     * @return  void
     */
    protected static function genSecretCode(IOInterface $io)
    {
        $file = getcwd() . '/etc/conf/system.php';

        $config = file_get_contents($file);

        $hash = 'Windwalker-' . hrtime(true);

        $salt = $io->ask("\nSalt to generate secret [{$hash}]: ", $hash);

        $config = str_replace('This-token-is-not-safe', md5(hrtime(true) . $salt), $config);

        file_put_contents($file, $config);

        $io->write('Auto created secret key.');
    }

    /**
     * Generate database config. will store in: etc/secret.yml.
     *
     * @param IOInterface $io
     *
     * @return  void
     */
    protected static function genSecretConfig(IOInterface $io)
    {
        $dist = getcwd() . '/.env.dist';
        $dest = getcwd() . '/.env';

        $env = file_get_contents($dist);

        if ($io->askConfirmation("\nDo you want to use database? [Y/n]: ", true)) {
            $vars = [];

            $supportedDrivers = [
                1 => 'mysql',
                2 => 'postgresql',
                3 => 'sqlsrv'
            ];

            $io->write('');
            $io->write('Supported database drivers:');

            foreach ($supportedDrivers as $k => $v) {
                $io->write(sprintf('  - [%s] %s', $k, $v));
            }

            $io->write('');

            $driver = $io->ask('Database driver [1]: ', '1');

            $vars['DATABASE_DRIVER']   = $supportedDrivers[$driver] ?? 'mysql';
            $vars['DATABASE_HOST']     = $io->ask('Database host [localhost]: ', 'localhost');
            $vars['DATABASE_NAME']     = $io->ask('Database name [acme]: ', 'acme');
            $vars['DATABASE_USER']     = $io->ask('Database user [root]: ', 'root');
            $vars['DATABASE_PASSWORD'] = $io->askAndHideAnswer('Database password: ');

            foreach ($vars as $key => $value) {
                $env = preg_replace('/' . $key . '=(.*)/', $key . '=' . $value, $env);
            }
        }

        file_put_contents($dest, $env);

        $io->write('');
        $io->write('Database config setting complete.');
        $io->write('');
    }
}
