<?php

declare(strict_types=1);

namespace Windwalker\Core\Composer;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;

class PackageUpgrader
{
    public static array $packages = [];

    public static string $uid;

    public static int $time;

    public static function upgrade(PackageEvent $event): void
    {
        static::$uid ??= uniqid('pkup__', true);
        static::$time = time();

        $root = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
        $tmpPath = $event->getComposer()->getPackage()->getExtra()['windwalker']['upgrade-tmp'] ?? 'tmp/upgrades.json';

        $tmpFile = new \SplFileObject($root . '/' . $tmpPath);

        $op = $event->getOperation();

        if ($op instanceof UpdateOperation) {
            $initial = $op->getInitialPackage();
            $target = $op->getTargetPackage();

            static::$packages[$target->getName()] = [
                $initial->getVersion(),
                $target->getVersion(),
            ];

            if (
                !is_dir($tmpFile->getPath())
                && !mkdir(
                    $concurrentDirectory = $tmpFile->getPath(),
                    0777,
                    true
                )
                && !is_dir($concurrentDirectory)
            ) {
                trigger_error(
                    sprintf('Directory "%s" was not created', $concurrentDirectory),
                    E_USER_WARNING
                );
            }

            file_put_contents(
                $tmpFile->getPathname(),
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
