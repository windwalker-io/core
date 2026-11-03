<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Router\NavMode;
use Windwalker\Core\Router\NavOptions;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Http\Response\RedirectResponse;

class RouteUriTest extends TestCase
{
    private function createNavigatorStub(string $absoluteReturn = '/absolute/result'): Navigator
    {
        $nav = $this->createStub(Navigator::class);
        $nav->method('getOptions')->willReturn(new NavOptions());
        $nav->method('absolute')->willReturn($absoluteReturn);

        return $nav;
    }

    #[Test]
    public function toString_withPlainUriDelegatesToNavigatorAbsolute(): void
    {
        $nav = $this->createNavigatorStub('/computed/show');

        $routeUri = new RouteUri('/show/{id}', ['id' => 5], $nav);

        self::assertSame('/computed/show', (string) $routeUri);
    }

    #[Test]
    public function toString_withRawModeSkipsAbsoluteResolution(): void
    {
        $nav = $this->createNavigatorStub('/should-not-be-used');

        $routeUri = new RouteUri('/show/{id}', ['id' => 5], $nav, new NavOptions(mode: NavMode::RAW));

        self::assertSame('show/%7Bid%7D?id=5', (string) $routeUri);
    }

    #[Test]
    public function toString_withClosureHandlerUsesHandledData(): void
    {
        $nav = $this->createNavigatorStub('/computed/closure');

        $handler = fn(array $vars) => ['/post/' . $vars['id'], []];

        $routeUri = new RouteUri($handler, ['id' => 42], $nav);

        self::assertSame('/computed/closure', (string) $routeUri);
    }

    #[Test]
    public function var_addsQueryVariable(): void
    {
        $nav = $this->createNavigatorStub();

        $routeUri = new RouteUri('/show', null, $nav);
        $new = $routeUri->var('id', 5);

        self::assertNotSame($routeUri, $new);
        self::assertSame(['id' => '5'], $new->getQueryValues());
    }

    #[Test]
    public function withVar_withAllowQueryArrayRestrictsAllowedVars(): void
    {
        $nav = $this->createNavigatorStub();

        $routeUri = new RouteUri('/show', null, $nav, new NavOptions(allowQuery: []));
        $new = $routeUri->withVar('id', 5);

        self::assertSame(['id'], $new->getOptions()->allowQuery);
    }

    #[Test]
    public function toResponse_returnsRedirectResponseWithStatus(): void
    {
        $nav = $this->createNavigatorStub('/computed');

        $routeUri = new RouteUri('/show', null, $nav);

        $response = $routeUri->toResponse(301);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(301, $response->getStatusCode());
    }

    #[Test]
    public function withStatus_clonesWithNewStatus(): void
    {
        $nav = $this->createNavigatorStub();

        $routeUri = new RouteUri('/show', null, $nav);
        $new = $routeUri->withStatus(302);

        self::assertSame(303, $routeUri->getStatus());
        self::assertSame(302, $new->getStatus());
    }
}
