<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Http\CorsHandler;
use Windwalker\Http\Response\Response;

class CorsHandlerTest extends TestCase
{
    #[Test]
    public function createBuildsHandlerWithDefaultResponseWhenNoneGiven(): void
    {
        $handler = CorsHandler::create();

        self::assertInstanceOf(Response::class, $handler->getResponse());
    }

    #[Test]
    public function createBuildsHandlerWithGivenResponse(): void
    {
        $response = new Response();
        $handler = CorsHandler::create($response);

        self::assertSame($response, $handler->getResponse());
    }

    #[Test]
    public function constructorDefaultsToNewResponseWhenNullGiven(): void
    {
        $handler = new CorsHandler();

        self::assertInstanceOf(Response::class, $handler->getResponse());
    }

    #[Test]
    public function allowOriginAddsWildcardHeaderByDefault(): void
    {
        $handler = new CorsHandler()->allowOrigin();

        self::assertSame('*', $handler->getResponse()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function allowOriginJoinsMultipleDomainsWithSpace(): void
    {
        $handler = new CorsHandler()->allowOrigin(['https://a.test', 'https://b.test']);

        self::assertSame(
            'https://a.test https://b.test',
            $handler->getResponse()->getHeaderLine('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function allowOriginAddsAdditionalHeaderValueByDefault(): void
    {
        $handler = new CorsHandler()
            ->allowOrigin('https://a.test')
            ->allowOrigin('https://b.test');

        self::assertSame(
            ['https://a.test', 'https://b.test'],
            $handler->getResponse()->getHeader('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function allowOriginReplacesHeaderWhenReplaceIsTrue(): void
    {
        $handler = new CorsHandler()
            ->allowOrigin('https://a.test')
            ->allowOrigin('https://b.test', replace: true);

        self::assertSame(
            ['https://b.test'],
            $handler->getResponse()->getHeader('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function allowMethodsUppercasesAndJoinsMethods(): void
    {
        $handler = new CorsHandler()->allowMethods(['get', 'post']);

        self::assertSame('GET, POST', $handler->getResponse()->getHeaderLine('Access-Control-Allow-Methods'));
    }

    #[Test]
    public function allowHeadersNormalizesAndJoinsHeaderNames(): void
    {
        $handler = new CorsHandler()->allowHeaders(['x-foo-bar', 'content-type']);

        self::assertSame(
            'X-Foo-Bar, Content-Type',
            $handler->getResponse()->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    #[Test]
    public function maxAgeSetsSecondsAsString(): void
    {
        $handler = new CorsHandler()->maxAge(3600);

        self::assertSame('3600', $handler->getResponse()->getHeaderLine('Access-Control-Max-Age'));
    }

    #[Test]
    public function allowCredentialsSetsTrueByDefault(): void
    {
        $handler = new CorsHandler()->allowCredentials();

        self::assertSame('true', $handler->getResponse()->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    #[Test]
    public function allowCredentialsCanBeSetToFalse(): void
    {
        $handler = new CorsHandler()->allowCredentials(false);

        self::assertSame('false', $handler->getResponse()->getHeaderLine('Access-Control-Allow-Credentials'));
    }

    #[Test]
    public function exposeHeadersNormalizesAndJoinsHeaderNames(): void
    {
        // Note: exposeHeaders() writes to the "Access-Control-Allow-Headers" header,
        // matching current source behavior (see CorsHandler::exposeHeaders()).
        $handler = new CorsHandler()->exposeHeaders(['x-foo', 'x-bar']);

        self::assertSame(
            'X-Foo, X-Bar',
            $handler->getResponse()->getHeaderLine('Access-Control-Allow-Headers')
        );
    }

    #[Test]
    public function handleCopiesAllHeadersToGivenResponse(): void
    {
        $handler = new CorsHandler()
            ->allowOrigin('https://a.test')
            ->allowMethods(['get']);

        $target = new Response();
        $result = $handler->handle($target);

        self::assertNotSame($target, $result);
        self::assertSame('https://a.test', $result->getHeaderLine('Access-Control-Allow-Origin'));
        self::assertSame('GET', $result->getHeaderLine('Access-Control-Allow-Methods'));
    }

    #[Test]
    public function handlePreservesExistingHeadersOnGivenResponse(): void
    {
        $handler = new CorsHandler()->allowOrigin('https://a.test');

        $target = new Response()->withHeader('X-Existing', 'keep-me');
        $result = $handler->handle($target);

        self::assertSame('keep-me', $result->getHeaderLine('X-Existing'));
        self::assertSame('https://a.test', $result->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function setResponseReplacesResponse(): void
    {
        $handler = new CorsHandler();
        $response = new Response();

        $new = $handler->setResponse($response);

        self::assertSame($handler, $new);
        self::assertSame($response, $handler->getResponse());
    }
}
