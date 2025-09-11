<?php

declare(strict_types=1);

namespace Windwalker\Core\Composer;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;

use function Windwalker\fs;
use function Windwalker\uid;

class PackageUpgrader
{
    public static array $packages = [];

    public static string $uid;

    public static int $time;

    public static function upgrade(PackageEvent $event)
    {
        include __DIR__ . '/../../vendor/autoload.php';

        static::$uid ??= uid();
        static::$time = time();

        $root = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
        $tmpPath = $event->getComposer()->getPackage()->getExtra()['windwalker']['upgrade-tmp'] ?? 'tmp/upgrades.json';
        $tmpFile = fs($tmpPath, $root);

        $op = $event->getOperation();

        if ($op instanceof UpdateOperation) {
            $initial = $op->getInitialPackage();
            $target = $op->getTargetPackage();

            static::$packages[$target->getName()] = [
                $initial->getVersion(),
                $target->getVersion(),
            ];

            $tmpFile->getParent()->mkdir();
            $tmpFile->write(
                json_encode(
                    [
                        'uid' => static::$uid,
                        'time' => static::$time,
                        'packages' => static::$packages,
                    ],
                    JSON_PRETTY_PRINT
                )
            );
        }
    }
}
