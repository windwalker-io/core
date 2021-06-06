<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Runtime;

use Windwalker\Core\Provider\RuntimeProvider;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;

/**
 * The Runtime class.
 *
 * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
}
