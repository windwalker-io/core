<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\Middleware\MetadataMiddleware;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class MetadataMiddlewareTest extends TestCase
{
    private AppContext $ctx;

    private HtmlFrame $htmlFrame;

    private function createMiddleware(array $meta = [], array $og = [], ?callable $handler = null): MetadataMiddleware
    {
        $app = TestWebAppFactory::create();
        $this->ctx = $app->createContext(TestWebAppFactory::request());

        $this->htmlFrame = new HtmlFrame($this->createStub(AssetService::class));
        $this->ctx->getContainer()->share(HtmlFrame::class, $this->htmlFrame);

        return $this->ctx->getContainer()->newInstance(
            MetadataMiddleware::class,
            ['meta' => $meta, 'og' => $og, 'handler' => $handler]
        );
    }

    private function handler(ResponseInterface $response): RequestHandlerInterface
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
    public function metadataTagsAreAddedToHtmlFrame(): void
    {
        $middleware = $this->createMiddleware(meta: ['description' => 'Hello World']);
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->handler($marker));

        self::assertSame($marker, $result);
        $rendered = $this->htmlFrame->renderMetadata();
        self::assertStringContainsString('name="description"', $rendered);
        self::assertStringContainsString('content="Hello World"', $rendered);
    }

    #[Test]
    public function multipleMetadataValuesAreAllAdded(): void
    {
        $middleware = $this->createMiddleware(meta: ['keywords' => ['php', 'windwalker']]);

        $middleware->process(TestWebAppFactory::request(), $this->handler(new Response()));

        $rendered = $this->htmlFrame->renderMetadata();
        self::assertStringContainsString('content="php"', $rendered);
        self::assertStringContainsString('content="windwalker"', $rendered);
    }

    #[Test]
    public function openGraphTagsAreAddedToHtmlFrame(): void
    {
        $middleware = $this->createMiddleware(og: ['og:title' => 'My Page']);

        $middleware->process(TestWebAppFactory::request(), $this->handler(new Response()));

        $rendered = $this->htmlFrame->renderMetadata();
        self::assertStringContainsString('property="og:title"', $rendered);
        self::assertStringContainsString('content="My Page"', $rendered);
    }

    #[Test]
    public function handlerCallableIsInvokedWithHtmlFrame(): void
    {
        $received = null;

        $middleware = $this->createMiddleware(
            handler: function (HtmlFrame $htmlFrame) use (&$received): void {
                $received = $htmlFrame;
            }
        );

        $middleware->process(TestWebAppFactory::request(), $this->handler(new Response()));

        self::assertSame($this->htmlFrame, $received);
    }

    #[Test]
    public function requestHandlerResultIsReturned(): void
    {
        $middleware = $this->createMiddleware();
        $marker = new Response();

        $result = $middleware->process(TestWebAppFactory::request(), $this->handler($marker));

        self::assertSame($marker, $result);
    }
}
