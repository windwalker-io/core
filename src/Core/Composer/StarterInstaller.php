<?php

declare(strict_types=1);

namespace Windwalker\Core\Composer;

use Composer\Script\Event;
use Exception;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\Crypt\SecretToolkit;

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
     * @param  Event  $event  The command event.
     *
     * @return  void
     * @throws Exception
     */
    public static function rootInstall(Event $event): void
    {
        include getcwd() . '/vendor/autoload.php';

        $io = $event->getIO();

        static::appName($event);

        static::noIgnoreLockFile($event);

        static::genEnv($event);

        // Complete
        $io->write('Install complete.');
    }

    public static function appName(Event $event): void
    {
        $io = $event->getIO();
        $name = trim((string) $io->ask('App Name: '));

        if (!$name) {
            return;
        }

        $file = getcwd() . '/etc/conf/app.php';

        $content = file_get_contents($file);
        $name = addslashes($name);

        $content = str_replace("'name' => 'Windwalker'", "'name' => '$name'", $content);

        file_put_contents($file, $content);
    }

    public static function noIgnoreLockFile(Event $event): void
    {
        $io = $event->getIO();

        $file = getcwd() . '/.gitignore';
        $ignore = file_get_contents($file);

        $ignore = str_replace(
            ['yarn.lock', 'composer.lock'],
            ['# yarn.lock', '# composer.lock'],
            $ignore
        );

        file_put_contents($file, $ignore);

        $io->write('Remove .lock files from .gitignore.');
    }

    /**
     * Generate database config. will store in: etc/secret.yml.
     *
     * @param  Event  $event
     *
     * @return  void
     * @throws Exception
     */
    public static function genEnv(Event $event): void
    {
        $composer = $_SERVER['COMPOSER_BINARY'] ?? null ?: 'composer';

        include getcwd() . '/vendor/autoload.php';

        $io = $event->getIO();

        $dist = getcwd() . '/.env.dist';
        $dest = getcwd() . '/.env';

        if (is_file($dest)) {
            $io->write('.env file already exists.');

            return;
        }

        $env = file_get_contents($dist);

        $vars = [];

        $secret = $io->ask(
            "\nEnter a custom secret [Leave empty to auto generate]: ",
            ''
        ) ?: static::genSecretCode();

        $vars['APP_SECRET'] = $secret;

        $installDb = false;

        if ($io->askConfirmation("\nDo you want to use database? [Y/n]: ", true)) {
            $supportedDrivers = [
                'pdo_mysql',
                'mysqli',
                'pdo_pgsql',
                'pgsql',
                'pdo_sqlsrv',
                'sqlsrv',
                'pdo_sqlite',
            ];

            $io->write('Please select database drivers: ');

            foreach ($supportedDrivers as $i => $driver) {
                $j = $i + 1;
                $io->write("  [$j] $driver");
            }

            $k = $io->ask('> ');

            $driver = $supportedDrivers[$k - 1] ?? 'pdo_mysql';

            $io->write('Selected driver: ' . $driver);

            $vars['DATABASE_DRIVER'] = $driver;
            $vars['DATABASE_HOST'] = $io->ask('Database host [localhost]: ', 'localhost');
            $vars['DATABASE_NAME'] = $io->ask('Database name [acme]: ', 'acme');
            $vars['DATABASE_USER'] = $io->ask('Database user [root]: ', 'root');
            $vars['DATABASE_PASSWORD'] = $io->askAndHideAnswer('Database password: ');

            $installDb = true;
        }

        foreach ($vars as $key => $value) {
            $env = static::injectEnvVar($env, $key, (string) $value);
        }

        file_put_contents($dest, $env);

        if ($installDb) {
            exec($composer . ' require windwalker/orm:^4.0');
        }

        $io->write('');
        $io->write('Env setting complete.');
        $io->write('');
    }

    /**
     * Generate secret code.
     *
     * Use length 16 to generate 128bit secret.
     *
     * @return string
     * @throws Exception
     */
    public static function genSecretCode(): string
    {
        if (class_exists(SecretToolkit::class)) {
            return SecretToolkit::genSecret();
        }

        return 'base64url:' . Base64Url::encode(random_bytes(16));
    }

    /**
     * @param  string  $env
     * @param  string  $key
     * @param  string  $value
     * @param  bool    $prepend
     *
     * @return string
     */
    protected static function injectEnvVar(string $env, string $key, string $value, bool $prepend = true): string
    {
        $value = static::handleEnvVar($value);

        if (str_contains($env, $key)) {
            return preg_replace(
                '/' . $key . '=(.*)/',
                $key . '=' . $value,
                $env
            );
        }

        $var = "{$key}={$value}";

        if ($prepend) {
            return $var . "\n" . $env;
        }

        return $env . "\n" . $var;
    }

    public static function handleEnvVar(string $value): string
    {
        $value = addcslashes($value, '"');

        if (preg_match('/\s|\$|\{|\}|\\|\*|;/', $value)) {
            $value = '"' . $value . '"';
        }

        return $value;
    }
}
