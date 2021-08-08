<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Composer\InstalledVersions;
use Windwalker\DI\Container;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

/**
 * The AbstractPackage class.
 */
abstract class AbstractPackage
{
    protected static ?string $name = null;

    protected static ?array $composer = null;

    abstract public function install(PackageInstaller $installer): void;

    public static function getName(): string
    {
        $name = static::$name ?? static::composerJson()['name'] ?? null;

        if ($name !== null) {
            return $name;
        }

        $ns = (new \ReflectionClass(static::class))->getShortName();

        return StrNormalize::toKebabCase(Str::removeRight(static::class, 'Package'));
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
    public static function composerJson(): ?array
    {
        return static::$composer[static::class] ??= static::loadComposerJson();
    }

    /**
     * loadComposerJson
     *
     * @return  array|null
     *
     * @throws \JsonException
     */
    protected static function loadComposerJson(): ?array
    {
        $file = static::composerJsonFile();

        if (!is_file($file)) {
            return null;
        }

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

    protected function mergeConfig(Container $container, array $data, bool $override = false): void
    {
        $container->getParameters()->transform(
            function ($storage) use ($override, $data) {
                if ($override) {
                    return Arr::mergeRecursive(
                        $data,
                        $storage,
                    );
                }
                return Arr::mergeRecursive(
                    $storage,
                    $data
                );
            }
        );
    }
}
