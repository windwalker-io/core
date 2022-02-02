<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Runtime;

use Windwalker\Core\Provider\RuntimeProvider;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\Utilities\Arr;

/**
 * The Runtime class.
 *
 * @since  4.0
 */
class Runtime
{
    protected static string $rootDir = '';

    protected static string $workDir = '';

    protected static bool $booted = false;

    protected static ?Container $container = null;

    /**
     * Runtime constructor.
     */
    protected function __construct()
    {
    }

    public static function boot(string $rootDir, string $workDir): void
    {
        if (!static::isBooted()) {
            static::$rootDir = $rootDir;
            static::$workDir = $workDir;

            $container = static::getContainer();

            $container->registerServiceProvider(new RuntimeProvider());
        }

        static::$booted = true;
    }

    public static function getConfig(): Config
    {
        return static::$container->getParameters();
    }

    public static function loadConfig(mixed $source, ?string $format = null, array $options = []): Collection
    {
        $container = self::getContainer();

        return $container->loadParameters($source, $format, $options);
    }

    /**
     * get
     *
     * @param  string  $name
     * @param  string  $delimiter
     *
     * @return  mixed
     */
    public static function &config(string $name, string $delimiter = '.')
    {
        return static::$container->getParameters()->getDeep($name, $delimiter);
    }

    public static function getContainer(int $options = 0): Container
    {
        return static::$container ??= (new Container(null, $options))->setParameters(new Config());
    }

    /**
     * Method to get property Booted
     *
     * @return  bool
     *
     * @since  4.0
     */
    public static function isBooted(): bool
    {
        return static::$booted;
    }

    /**
     * @return string
     */
    public static function getRootDir(): string
    {
        return self::$rootDir;
    }

    /**
     * @return string
     */
    public static function getWorkDir(): string
    {
        return self::$workDir;
    }

    public static function ipBlock(array|string|null $forEnv, string|array|null $allowIps): void
    {
        if (!$forEnv) {
            return;
        }

        if (!in_array((string) env('APP_ENV'), (array) $forEnv, true)) {
            return;
        }

        if (!is_array($allowIps)) {
            $allowIps = Arr::explodeAndClear(',', (string) $allowIps);
        }

        if (in_array('all', $allowIps, true) || in_array('REMOTE_ADDR', $allowIps, true)) {
            return;
        }

        $allowIps = array_merge(['127.0.0.1', 'fe80::1', '::1'], $allowIps);

        // Get allow remote ips from config.
        if (
            isset($_SERVER['HTTP_CLIENT_IP'])
            || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            || !in_array(@$_SERVER['REMOTE_ADDR'], $allowIps, true)
        ) {
            header('HTTP/1.1 403 Forbidden');

            exit('Forbidden');
        }
    }
}
