<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Asset\AssetProvider;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Renderer\EdgeRenderer;
use Windwalker\Renderer\Provider\RendererProvider;

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

    /**
     * Builds a WebApplication wired with a real view-rendering pipeline
     * (RendererProvider + AssetProvider, an Edge template engine, and the
     * `@root`/`@views`/`@public` path aliases those providers need).
     *
     * `RendererService`/`HtmlFrame`/`AssetService` are not registered by Core
     * itself; real host apps wire them via `windwalker/framework`'s
     * RendererProvider/AssetProvider. `AssetService` ultimately depends on a
     * REQUEST-layer `EventDispatcherRegistry`, so the Container passed to
     * WebApplication must already sit one layer above `create()`'s plain
     * `new Container()` to match `WebApplication::createContext()`'s own
     * "Level 2" assumption (see its doc comments) - hence the extra
     * `createChild()` below, local to this factory method only.
     */
    public static function createWithRendering(array $config = []): WebApplication
    {
        $viewsPath = __DIR__ . '/views';

        $params = new Config();
        $rootContainer = new Container()->setParameters($params);
        $container = $rootContainer->createChild();
        $container->share(Config::class, $params);

        $app = new WebApplication($container);
        $app->loadConfig(['app' => $config + ['name' => 'TestApp', 'debug' => false, 'secret' => 'test-secret']]);
        $app->loadConfig(
            [
                '@root' => __DIR__,
                '@views' => $viewsPath,
                '@public' => $viewsPath,
                'renderer' => [
                    'paths' => [$viewsPath],
                    'renderers' => [
                        'edge' => [EdgeRenderer::class, ['edge.php']],
                    ],
                    'providers' => [
                        RendererProvider::class,
                    ],
                ],
                'asset' => [
                    'import_map' => [],
                    'namespace_base' => 'Windwalker\\',
                    'providers' => [
                        AssetProvider::class,
                    ],
                ],
            ]
        );

        return $app;
    }
}
