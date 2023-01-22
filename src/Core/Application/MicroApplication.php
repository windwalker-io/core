<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Middleware\RoutingMiddleware;
use Windwalker\Core\Router\Router;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Http\Factory\ServerRequestFactory;
use Windwalker\Http\Output\Output;

/**
 * The MicroApplication class.
 */
class MicroApplication extends WebApplication
{
    /**
     * WebApplication constructor.
     *
     * @param  Container  $container
     */
    public function __construct(?Container $container = null)
    {
        $config = new Config();

        $this->container = $container ?? (new Container())->setParameters($config);
        $this->container->share(Config::class, $config);
    }

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        parent::boot();

        $this->addMiddleware(RoutingMiddleware::class);
    }

    public function routing(string|iterable|callable $routes): static
    {
        $this->boot();

        $this->service(Router::class)->register($routes);

        return $this;
    }

    public function execute(?ServerRequestInterface $request = null, ?callable $handler = null): ResponseInterface
    {
        $request ??= ServerRequestFactory::createFromGlobals();

        $res = parent::execute($request, $handler);

        $this->service(Output::class)->respond($res);

        return $res;
    }
}
