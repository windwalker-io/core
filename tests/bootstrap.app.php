<?php

/**
 * PHPUnit bootstrap for running Core tests inside a host application.
 *
 * Referenced by phpunit.app.dist.xml. It loads the host's autoloader and
 * etc/define.php, boots Runtime, and prepares TestApp::$instance and
 * TestApp::$appContext, which the traits in tests/Testing rely on.
 *
 * The host root is taken from the WINDWALKER_TEST_APP_ROOT environment
 * variable, otherwise from the conventional vendor/windwalker/core location.
 */

declare(strict_types=1);

use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\Tests\TestApp;

$root = getenv('WINDWALKER_TEST_APP_ROOT') ?: dirname(__DIR__, 4);

require $root . '/vendor/autoload.php';

// The host's composer autoloader does not load Core's autoload-dev mapping.
spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Windwalker\\Tests\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
);

require_once $root . '/etc/define.php';

Runtime::boot(WINDWALKER_ROOT, WINDWALKER_PUBLIC);
Runtime::loadConfig(WINDWALKER_ROOT . '/etc/runtime.config.php');

// Under the CLI, the app type is CLI_WEB, and database boot asks for the CLI server inspector.
CliServerRuntime::boot('test', []);

$main = Runtime::getContainer()->resolve('factories.apps.main');

TestApp::$instance = new TestApp($main->getContainer()->createChild());
TestApp::$instance->prepareTestContext();
