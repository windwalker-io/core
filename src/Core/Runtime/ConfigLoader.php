<?php

declare(strict_types=1);

namespace Windwalker\Core\Runtime;

use Windwalker\Core\Attributes\ConfigModule;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Attributes\AttributesAccessor;

class ConfigLoader
{
    public static function includeArrays(string $path, array $contextData = []): array
    {
        $resultBag = [];

        extract($contextData, EXTR_OVERWRITE);
        unset($contextData);

        foreach (glob($path) as $file) {
            $filename = static::getFileName($file);

            $included = include $file;

            if ($included instanceof \Closure) {
                $ref = new \ReflectionFunction($included);
                $module = $ref->getAttributes(ConfigModule::class)[0] ?? null;

                $module = $module ? $module->newInstance() : new ConfigModule();

                $module->config = $included();

                $resultBag[$filename] = $module;
            } else {
                foreach ($included as $name => $subConfig) {
                    $module = new ConfigModule();
                    $module->config = $subConfig;

                    $resultBag[$name] = $module;
                }
            }
        }

        uasort($resultBag, function (ConfigModule $a, ConfigModule $b) {
            return $a->ordering <=> $b->ordering;
        });

        $resultConfigs = [];

        /** @var ConfigModule $module */
        foreach ($resultBag as $name => $module) {
            if ($module->path !== null) {
                Arr::set($resultBag, $module->path, $module->config);
            } else {
                $resultConfigs[$name] = $module->config;
            }
        }

        return Arr::mapGenerator(
            function () use ($resultBag) {

                foreach ($resultBag as $name => $result) {
                    $path
                }
            }
        );
    }

    public static function getFileName(string $filePath): string
    {
        return basename($filePath, '.php');
    }
}
