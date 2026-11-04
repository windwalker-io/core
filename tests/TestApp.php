<?php

declare(strict_types=1);

namespace Windwalker\Tests;

use Windwalker\Core\Testing\AbstractTestApp;
use Windwalker\Core\Testing\Database\TestDatabasesProvider;

/**
 * Reference example of a host app's app-backed test app.
 *
 * Core's own test suite uses this as its {@see AbstractTestApp} subclass; a
 * consuming app can copy this file (rename the class/namespace as needed)
 * and wire it up from its own PHPUnit bootstrap the same way
 * `tests/bootstrap.app.php` does here. See {@see AbstractTestApp} for the
 * shared lifecycle this class inherits, and
 * `.github/docs/host-application-and-testing.md` for the full setup guide.
 */
class TestApp extends AbstractTestApp
{
    protected static function getContextProviders(): array
    {
        return [
            fn() => new TestDatabasesProvider(env('WINDWALKER_TEST_DB_DSN')),
        ];
    }
}
