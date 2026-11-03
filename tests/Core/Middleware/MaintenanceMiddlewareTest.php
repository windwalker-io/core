<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\Offline\MaintenanceConfig;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Middleware\MaintenanceMiddleware;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\Response;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Handler\ArrayHandler;
use Windwalker\Session\Session;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class MaintenanceMiddlewareTest extends TestCase
{
    private string $file;

    #[Before]
    protected function setUpFile(): void
    {
        $this->file = sys_get_temp_dir() . '/ww-maintenance-' . uniqid() . '.json';
    }

    #[After]
    protected function removeFile(): void
    {
        @unlink($this->file);
    }

    private function createManager(?MaintenanceConfig $config = null): MaintenanceManager
    {
        $file = $this->file;

        $manager = new class ($file) extends MaintenanceManager {
            public function __construct(protected string $path) {}

            public function getFilePath(): string
            {
                return $this->path;
            }
        };

        if ($config !== null) {
            $manager->down($config);
        }

        return $manager;
    }

    private function createApp(?MaintenanceConfig $config, string $clientIp = '127.0.0.1'): WebApplication
    {
        $app = TestWebAppFactory::create();

        $app->getContainer()->share(MaintenanceManager::class, $this->createManager($config));

        $serverRequest = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => $clientIp],
            uri: 'http://example.com/foo',
            method: 'GET',
        );

        $appRequest = new AppRequest(
            $serverRequest,
            new SystemUri('http://example.com/foo'),
            $this->createStub(ProxyResolver::class),
            new RequestInspector(),
        );

        $app->getContainer()->share(AppRequestInterface::class, $appRequest);
        $app->getContainer()->share(
            Session::class,
            new Session([], new PhpBridge(handler: new ArrayHandler()))
        );

        return $app;
    }

    private function passthroughHandler(ResponseInterface $response): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            public function __construct(private ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    #[Test]
    public function handlerIsCalledWhenNotInMaintenance(): void
    {
        $app = $this->createApp(null);
        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertSame($marker, $result);
    }

    #[Test]
    public function handlerIsCalledWhenClientIpIsAllowed(): void
    {
        $config = new MaintenanceConfig()->setAllowedIps(['9.9.9.9']);
        $app = $this->createApp($config, clientIp: '9.9.9.9');
        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertSame($marker, $result);
    }

    #[Test]
    public function handlerIsCalledWhenSessionSecretAlreadyMatches(): void
    {
        $config = new MaintenanceConfig()->setSecret('mysecret');
        $app = $this->createApp($config);

        $app->getContainer()->get(Session::class)->set('maintenance_secret', 'mysecret');

        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertSame($marker, $result);
    }

    #[Test]
    public function handlerIsCalledWhenQuerySecretMatchesAndStoredInSession(): void
    {
        $config = new MaintenanceConfig()->setSecret('mysecret');
        $app = $this->createApp($config);

        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $request = new ServerRequest(
            uri: 'http://example.com/foo?mysecret=1',
            method: 'GET',
            queryParams: ['mysecret' => '1'],
        );

        $result = $middleware->process($request, $this->passthroughHandler($marker));

        self::assertSame($marker, $result);
        self::assertSame('mysecret', $app->getContainer()->get(Session::class)->get('maintenance_secret'));
    }

    #[Test]
    public function redirectResponseIsReturnedWhenSecretInvalidAndRedirectConfigured(): void
    {
        $config = new MaintenanceConfig()->setSecret('mysecret')->setRedirect('/offline');
        $app = $this->createApp($config);

        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertNotSame($marker, $result);
        self::assertSame(303, $result->getStatusCode());
        self::assertSame('/offline', $result->getHeaderLine('Location'));
    }

    #[Test]
    public function redirectResponseIsReturnedWhenNoAllowedIpsOrSecretConfigured(): void
    {
        $config = new MaintenanceConfig()->setRedirect('/offline');
        $app = $this->createApp($config);

        $middleware = new MaintenanceMiddleware($app);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertNotSame($marker, $result);
        self::assertSame(303, $result->getStatusCode());
        self::assertSame('/offline', $result->getHeaderLine('Location'));
    }

    #[Test]
    public function rendersRealMaintenanceViewWhenNoRedirectOrAccessOverrideConfigured(): void
    {
        $config = new MaintenanceConfig()->setSecret('mysecret');
        $app = TestWebAppFactory::createWithRendering();

        $app->getContainer()->share(MaintenanceManager::class, $this->createManager($config));

        $serverRequest = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            uri: 'http://example.com/foo',
            method: 'GET',
        );

        $ctx = $app->createContext($serverRequest);
        $ctx->getContainer()->share(
            AppRequestInterface::class,
            new AppRequest(
                $serverRequest,
                new SystemUri('http://example.com/foo'),
                $this->createStub(ProxyResolver::class),
                new RequestInspector(),
            )
        );
        $ctx->getContainer()->share(
            Session::class,
            new Session([], new PhpBridge(handler: new ArrayHandler()))
        );

        $middleware = new MaintenanceMiddleware($ctx);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->passthroughHandler($marker));

        self::assertNotSame($marker, $result);
        self::assertSame(503, $result->getStatusCode());
        self::assertStringContainsString('Maintenance: mysecret', (string) $result->getBody());
    }
}
