<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Composer\InstalledVersions;

/**
 * The AbstractPackage class.
 */
abstract class AbstractPackage
{
    protected static ?string $name = null;

    protected static ?array $composer = null;

    abstract public function boot(): void;

    abstract public function install(): void;

    public static function getName(): string
    {
        return static::$name ??= self::composerJson()['name'];
    }

    public static function version(): string
    {
        return InstalledVersions::getPrettyVersion(static::getName());
    }

    /**
     * composerJson
     *
     * @return  array
     *
     * @throws \JsonException
     */
    public static function composerJson(): array
    {
        return static::$composer ??= self::loadComposerJson();
    }

    /**
     * loadComposerJson
     *
     * @return  array
     *
     * @throws \JsonException
     */
    private static function loadComposerJson(): array
    {
        $file = static::composerJsonFile();

        return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }

    protected static function composerJsonFile(): string
    {
        return static::dir() . '/../composer.json';
    }

    /**
     * dir
     *
     * @return  string
     *
     * @since  3.5
     */
    public static function dir(): string
    {
        return dirname(static::fileName());
    }

    /**
     * file
     *
     * @return  string
     *
     * @since  3.5
     */
    public static function fileName(): string
    {
        return (new \ReflectionClass(static::class))->getFileName();
    }

    /**
     * getNamespace
     *
     * @return  string
     *
     * @since  3.1
     */
    public static function namespace(): string
    {
        return (new \ReflectionClass(static::class))->getNamespaceName();
    }
}
