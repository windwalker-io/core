<?php

declare(strict_types=1);

namespace Windwalker\Core\Runtime;

use Windwalker\Core\Attributes\ConfigModule;
use Windwalker\Utilities\Iterator\PriorityQueue;

class ConfigLoader
{
    public static function includeArrays(string $path, array $contextData = []): array
    {
        $loaded = [];
        $queue = new PriorityQueue();

        extract($contextData, EXTR_OVERWRITE);
        unset($contextData);

        foreach (glob($path) as $file) {
            $filename = static::getFileName($file);

            $included = include $file;

            if ($included instanceof \Closure || !array_is_list($included)) {
                $included = [$included];
            }

            foreach ($included as $item) {
                if ($item instanceof \Closure) {
                    $ref = new \ReflectionFunction($item);
                    $module = $ref->getAttributes(ConfigModule::class)[0] ?? null;

                    $module = $module ? $module->newInstance() : new ConfigModule();

                    if (!$module->isAvailable()) {
                        continue;
                    }

                    $module->callback = $item;

                    $queue->insert($module, $module->priority);
                } else {
                    foreach ($item as $name => $subConfig) {
                        $module = new ConfigModule();
                        $module->name = $name;
                        $module->config = $subConfig;

                        $queue->insert($module, $module->priority);
                    }
                }

                $module->file = $file;
            }
        }

        $resultConfigs = [];

        /** @var ConfigModule $module */
        foreach ($queue as $module) {
            if ($module->name !== null) {
                $resultConfigs[$module->name] = $module->config;
            } else {
                $resultConfigs += $module->config;
            }
        }

        return $resultConfigs;
    }

    public static function getFileName(string $filePath): string
    {
        return basename($filePath, '.php');
    }
}
