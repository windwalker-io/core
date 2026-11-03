<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Context;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Tests\Core\Application\Stub\AppRequestTraitStub;
use Windwalker\Uri\Uri;

/**
 * Covers AppRequestTrait through its only consumer, AppRequest, built from a real ServerRequest.
 */
class AppRequestTraitTest extends TestCase
{
    private function createRequest(
        ?ServerRequestInterface $request = null,
        ?ProxyResolver $proxyResolver = null,
    ): AppRequest {
        $request ??= new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '10.0.0.1'],
            uri: 'http://example.com/foo?a=1&b=2',
            method: 'GET',
            body: 'php://memory',
            headers: ['X-Test' => ['one', 'two'], 'Accept' => ['text/html']],
            queryParams: ['a' => '1', 'b' => '2'],
            parsedBody: ['b' => 'body', 'c' => '3'],
        );

        return new AppRequest(
            $request,
            new SystemUri('http://example.com/foo'),
            $proxyResolver ?? $this->createStub(ProxyResolver::class),
            new RequestInspector(),
        );
    }

    #[Test]
    public function getClientIpUsesRemoteAddr(): void
    {
        self::assertSame('10.0.0.1', $this->createRequest()->getClientIP());
    }

    #[Test]
    public function getClientIpUsesTrustedProxyIpWhenProxied(): void
    {
        $proxy = $this->createStub(ProxyResolver::class);
        $proxy->method('isProxy')->willReturn(true);
        $proxy->method('getTrustedProxyIP')->willReturn('9.9.9.9');

        self::assertSame('9.9.9.9', $this->createRequest(proxyResolver: $proxy)->getClientIP());
    }

    #[Test]
    public function withClientIpOverridesAndKeepsOriginalImmutable(): void
    {
        $req = $this->createRequest();
        $new = $req->withClientIp('1.2.3.4');

        self::assertNotSame($req, $new);
        self::assertSame('1.2.3.4', $new->getClientIP());
        self::assertSame('10.0.0.1', $req->getClientIP());
    }

    #[Test]
    public function withUriReplacesRequestUriImmutably(): void
    {
        $req = $this->createRequest();
        $new = $req->withUri(new Uri('http://other.test/bar'));

        self::assertSame('http://other.test/bar', (string) $new->getUri());
        self::assertSame('http://example.com/foo?a=1&b=2', (string) $req->getUri());
    }

    #[Test]
    public function getQueryValuesMergesUrlVarsAndQuery(): void
    {
        $req = $this->createRequest()->withMatchedRoute(new Route('r', '/foo')->vars(['id' => '5', 'a' => 'var']));

        // Query values win over URL vars on key conflict (array_merge order).
        self::assertSame(['id' => '5', 'a' => '1', 'b' => '2'], $req->getQueryValues());
    }

    #[Test]
    public function getQueryValuesWithoutRouteUsesQueryOnly(): void
    {
        self::assertSame(['a' => '1', 'b' => '2'], $this->createRequest()->getQueryValues());
    }

    #[Test]
    public function withUrlVarsSetsVarsAndResetsInputCache(): void
    {
        $req = $this->createRequest()->withMatchedRoute(new Route('r', '/foo'));
        $req->input();

        $new = $req->withUrlVars(['id' => '7']);

        self::assertSame(['id' => '7'], $new->getUrlVars());
        self::assertSame('7', $new->input('id'));
    }

    #[Test]
    public function withInputOverridesInput(): void
    {
        $req = $this->createRequest();
        $new = $req->withInput(['x' => 'y']);

        self::assertSame(['x' => 'y'], $new->getInput());
        self::assertSame([], $req->getInput());
    }

    #[Test]
    public function withMatchedRouteResetsInput(): void
    {
        $req = $this->createRequest();
        $req->input();

        $route = new Route('r', '/foo')->vars(['z' => '1']);
        $new = $req->withMatchedRoute($route);

        self::assertSame($route, $new->getMatchedRoute());
        self::assertSame('1', $new->input('z'));
    }

    #[Test]
    public function getHeaderAndHeaders(): void
    {
        $req = $this->createRequest();

        self::assertSame('one,two', $req->getHeader('X-Test'));
        self::assertSame('one,two', $req->getHeaders()['X-Test']);
        self::assertSame(['one', 'two'], $req->getHeaders(false)['X-Test']);
    }

    #[Test]
    public function inputWithoutFieldsReturnsCollectionOfMergedInput(): void
    {
        $input = $this->createRequest()->input();

        self::assertInstanceOf(Collection::class, $input);
        // Body values override query values on conflict.
        self::assertSame(['a' => '1', 'b' => 'body', 'c' => '3'], $input->dump());
    }

    #[Test]
    public function inputWithSingleFieldReturnsValue(): void
    {
        $req = $this->createRequest();

        self::assertSame('body', $req->input('b'));
        self::assertNull($req->input('missing'));
    }

    #[Test]
    public function inputWithMultipleFieldsReturnsCollection(): void
    {
        $data = $this->createRequest()->input('a', 'c', 'missing');

        self::assertInstanceOf(Collection::class, $data);
        self::assertSame(['a' => '1', 'c' => '3', 'missing' => null], $data->dump());
    }

    #[Test]
    public function inputWithFilterRulesThrowsValidateFailExceptionWhenInvalid(): void
    {
        $this->expectException(ValidateFailException::class);

        $this->createRequest()->input(missing: 'required');
    }

    #[Test]
    public function inputWithFilterRulesPassesWhenValid(): void
    {
        self::assertSame('1', $this->createRequest()->input(a: 'required'));
    }

    #[Test]
    public function withSystemUriReplacesUriImmutably(): void
    {
        $req = $this->createRequest();
        $uri = new SystemUri('http://new.test/');
        $new = $req->withSystemUri($uri);

        self::assertSame($uri, $new->getSystemUri());
        self::assertNotSame($uri, $req->getSystemUri());
    }

    #[Test]
    public function jsonSerializeContainsSystemUriAndNullStream(): void
    {
        $data = $this->createRequest()->jsonSerialize();

        self::assertArrayHasKey('systemUri', $data);
        self::assertNull($data['stream']);
    }

    /**
     * AppRequest::compileInput() always merges to an array, so the non-array
     * fallbacks in input() are otherwise only reachable through WsAppRequest,
     * which requires the uninstalled windwalker/reactor package. Exercise them
     * directly via a minimal AppRequestTrait host instead.
     */
    #[Test]
    public function inputWithoutFieldsReturnsScalarInputAsIsWhenNotArray(): void
    {
        $request = new AppRequestTraitStub('raw-scalar-payload');

        self::assertSame('raw-scalar-payload', $request->input());
    }

    #[Test]
    public function inputWithFieldsReturnsScalarInputAsIsWhenNotArray(): void
    {
        $request = new AppRequestTraitStub('raw-scalar-payload');

        self::assertSame('raw-scalar-payload', $request->input('foo', 'bar'));
    }
}
