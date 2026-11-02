<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Http\Request\ServerRequest;

/**
 * Builds a real, isolated WebApplication (own Container and Config) for stage-4 integration tests.
 *
 * Unlike tests/TestApp.php it needs no database or project config. Remove it if
 * the Application tests no longer boot a real application.
 */
final class TestWebAppFactory
{
    public static function create(array $config = []): WebApplication
    {
        $params = new Config();
        $container = new Container()->setParameters($params);
        $container->share(Config::class, $params);

        $app = new WebApplication($container);
        $app->loadConfig(['app' => $config + ['name' => 'TestApp', 'debug' => false, 'secret' => 'test-secret']]);

        return $app;
    }

    public static function request(
        string $uri = 'http://example.com/foo',
        string $method = 'GET',
        array $headers = [],
    ): ServerRequestInterface {
        return new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            uri: $uri,
            method: $method,
            body: 'php://memory',
            headers: $headers,
        );
    }
}
