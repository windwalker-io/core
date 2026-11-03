<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Http\Request\ServerRequest;

/**
 * Covers AppRequest's own behavior; trait-level behavior inherited from
 * AppRequestTrait is covered by AppRequestTraitTest.
 */
class AppRequestTest extends TestCase
{
    private function createRequest(?ServerRequestInterface $request = null): AppRequest
    {
        $request ??= new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '10.0.0.1'],
            uri: 'http://example.com/foo?a=1',
            method: 'GET',
            parsedBody: ['b' => 'body'],
        );

        return new AppRequest(
            $request,
            new SystemUri('http://example.com/foo'),
            $this->createStub(ProxyResolver::class),
            new RequestInspector(),
        );
    }

    #[Test]
    public function getMethodReturnsRequestMethod(): void
    {
        self::assertSame('GET', $this->createRequest()->getMethod());
    }

    #[Test]
    public function getOverrideMethodPrefersHeaderOverride(): void
    {
        $request = new ServerRequest(
            method: 'POST',
            headers: ['X-Http-Method-Override' => ['DELETE']],
            parsedBody: ['_method' => 'PUT'],
        );

        self::assertSame('DELETE', $this->createRequest($request)->getOverrideMethod());
    }

    #[Test]
    public function getOverrideMethodFallsBackToParsedBodyMethodField(): void
    {
        $request = new ServerRequest(
            method: 'POST',
            parsedBody: ['_method' => 'PATCH'],
        );

        self::assertSame('PATCH', $this->createRequest($request)->getOverrideMethod());
    }

    #[Test]
    public function getOverrideMethodFallsBackToQueryMethodFieldWhenNoBodyOverride(): void
    {
        $request = new ServerRequest(
            uri: 'http://example.com/foo?_method=PUT',
            method: 'POST',
            queryParams: ['_method' => 'PUT'],
        );

        self::assertSame('PUT', $this->createRequest($request)->getOverrideMethod());
    }

    #[Test]
    public function getOverrideMethodFallsBackToRequestMethodWhenNoOverridesPresent(): void
    {
        $request = new ServerRequest(method: 'POST');

        self::assertSame('POST', $this->createRequest($request)->getOverrideMethod());
    }

    #[Test]
    public function withRequestReplacesRequestAndResetsInputImmutably(): void
    {
        $req = $this->createRequest();
        $req->input();

        $newRequest = new ServerRequest(method: 'DELETE');
        $new = $req->withRequest($newRequest);

        self::assertNotSame($req, $new);
        self::assertSame('DELETE', $new->getMethod());
        self::assertSame('GET', $req->getMethod());
    }

    #[Test]
    public function inputWithMethodDefaultsToCompiledInput(): void
    {
        self::assertSame(['a' => '1', 'b' => 'body'], $this->createRequest()->inputWithMethod());
    }

    #[Test]
    public function inputWithMethodReturnsQueryValuesForGet(): void
    {
        self::assertSame(['a' => '1'], $this->createRequest()->inputWithMethod('GET'));
    }

    #[Test]
    public function inputWithMethodReturnsBodyValuesForPost(): void
    {
        self::assertSame(['b' => 'body'], $this->createRequest()->inputWithMethod('POST'));
    }

    #[Test]
    public function inputWithMethodFetchesSingleFieldFromSelectedSource(): void
    {
        self::assertSame('1', $this->createRequest()->inputWithMethod('GET', 'a'));
    }

    #[Test]
    public function inputWithMethodFetchesMultipleFieldsAsCollection(): void
    {
        $result = $this->createRequest()->inputWithMethod('REQUEST', 'a', 'b');

        self::assertInstanceOf(Collection::class, $result);
        self::assertSame(['a' => '1', 'b' => 'body'], $result->dump());
    }

    #[Test]
    public function fileWithoutFieldsReturnsAllUploadedFiles(): void
    {
        $file = $this->createStub(UploadedFileInterface::class);
        $request = new ServerRequest(uploadedFiles: ['avatar' => $file]);

        self::assertSame(['avatar' => $file], $this->createRequest($request)->file());
    }

    #[Test]
    public function fileWithSingleFieldReturnsSingleUploadedFile(): void
    {
        $file = $this->createStub(UploadedFileInterface::class);
        $request = new ServerRequest(uploadedFiles: ['avatar' => $file]);

        self::assertSame($file, $this->createRequest($request)->file('avatar'));
    }

    #[Test]
    public function fileWithMissingFieldReturnsNull(): void
    {
        $request = new ServerRequest(uploadedFiles: []);

        self::assertNull($this->createRequest($request)->file('avatar'));
    }

    #[Test]
    public function fileWithMultipleFieldsReturnsCollectionKeyedByField(): void
    {
        $avatar = $this->createStub(UploadedFileInterface::class);
        $banner = $this->createStub(UploadedFileInterface::class);
        $request = new ServerRequest(uploadedFiles: ['avatar' => $avatar, 'banner' => $banner]);

        $result = $this->createRequest($request)->file('avatar', 'banner', 'missing');

        self::assertInstanceOf(Collection::class, $result);
        self::assertSame(
            ['avatar' => $avatar, 'banner' => $banner, 'missing' => null],
            $result->dump()
        );
    }

    #[Test]
    public function getUrlVarsReturnsEmptyArrayWithoutMatchedRoute(): void
    {
        self::assertSame([], $this->createRequest()->getUrlVars());
    }

    #[Test]
    public function getUrlVarsReturnsMatchedRouteVarsWhenPresent(): void
    {
        $route = new Route('r', '/foo')->vars(['id' => '42']);
        $req = $this->createRequest()->withMatchedRoute($route);

        self::assertSame(['id' => '42'], $req->getUrlVars());
    }

    #[Test]
    public function getBodyValuesReturnsParsedBody(): void
    {
        self::assertSame(['b' => 'body'], $this->createRequest()->getBodyValues());
    }

    #[Test]
    public function isAcceptDelegatesToRequestInspector(): void
    {
        $request = new ServerRequest(headers: ['Accept' => ['application/json']]);

        self::assertTrue($this->createRequest($request)->isAccept('application/json'));
        self::assertFalse($this->createRequest($request)->isAccept('text/html'));
    }

    #[Test]
    public function isAcceptJsonChecksForJsonAcceptHeader(): void
    {
        $accepts = new ServerRequest(headers: ['Accept' => ['application/json']]);
        $rejects = new ServerRequest(headers: ['Accept' => ['text/html']]);

        self::assertTrue($this->createRequest($accepts)->isAcceptJson());
        self::assertFalse($this->createRequest($rejects)->isAcceptJson());
    }

    #[Test]
    public function isApiCallDelegatesToRequestInspector(): void
    {
        $request = new ServerRequest(headers: ['Content-Type' => ['application/json']]);

        self::assertTrue($this->createRequest($request)->isApiCall());
    }
}
