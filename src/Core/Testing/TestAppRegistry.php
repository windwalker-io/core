<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing;

/**
 * Holds the app-backed test app of the running test process.
 *
 * Core's testing helpers ({@see Database\TestDatabaseFixtures},
 * {@see Traits\ServiceAwareTestTrait}) resolve the app from here, so they
 * do not depend on which concrete TestApp (Core's, a package's, a host's)
 * is running. Register it once from the PHPUnit bootstrap:
 *
 *     TestAppRegistry::use(TestApp::createInstance(...));
 */
final class TestAppRegistry
{
    public static ?AbstractTestApp $instance = null;

    public static function use(AbstractTestApp $app): AbstractTestApp
    {
        return static::$instance = $app;
    }

    public static function instance(): AbstractTestApp
    {
        return static::$instance ?? throw new \RuntimeException(
            'No test app registered. Call TestAppRegistry::use($app) in your PHPUnit bootstrap.'
        );
    }

    public static function reset(): void
    {
        static::$instance = null;
    }
}
