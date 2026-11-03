<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Http\Request\ServerRequest;

class RequestInspectorTest extends TestCase
{
    #[Test]
    public function isApiCallDetectsXmlHttpRequestHeader(): void
    {
        $request = new ServerRequest(serverParams: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        self::assertTrue(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function isApiCallDetectsXmlHttpRequestHeaderCaseInsensitively(): void
    {
        $request = new ServerRequest(serverParams: ['HTTP_X_REQUESTED_WITH' => 'xmlhttprequest']);

        self::assertTrue(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function isApiCallDetectsJsonContentType(): void
    {
        $request = new ServerRequest(headers: ['Content-Type' => ['application/json']]);

        self::assertTrue(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function isApiCallDetectsApplicationXmlContentType(): void
    {
        $request = new ServerRequest(headers: ['Content-Type' => ['application/xml']]);

        self::assertTrue(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function isApiCallDetectsTextXmlContentType(): void
    {
        $request = new ServerRequest(headers: ['Content-Type' => ['text/xml']]);

        self::assertTrue(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function isApiCallReturnsFalseForRegularHtmlRequest(): void
    {
        $request = new ServerRequest(headers: ['Content-Type' => ['text/html']]);

        self::assertFalse(new RequestInspector()->isApiCall($request));
    }

    #[Test]
    public function setApiCallDetectorOverridesDetectionLogic(): void
    {
        $inspector = new RequestInspector()->setApiCallDetector(static fn() => true);

        self::assertTrue($inspector->isApiCall(new ServerRequest()));
    }

    #[Test]
    public function getApiCallDetectorLazilyCreatesAndCachesDefaultDetector(): void
    {
        $inspector = new RequestInspector();

        $detector = $inspector->getApiCallDetector();

        self::assertInstanceOf(\Closure::class, $detector);
        self::assertSame($detector, $inspector->getApiCallDetector());
    }

    #[Test]
    public function isAcceptReturnsTrueWhenAcceptHeaderContainsType(): void
    {
        $request = new ServerRequest(headers: ['Accept' => ['application/json, text/html']]);

        self::assertTrue(new RequestInspector()->isAccept($request, 'application/json'));
    }

    #[Test]
    public function isAcceptReturnsFalseWhenAcceptHeaderDoesNotContainType(): void
    {
        $request = new ServerRequest(headers: ['Accept' => ['text/html']]);

        self::assertFalse(new RequestInspector()->isAccept($request, 'application/json'));
    }
}
