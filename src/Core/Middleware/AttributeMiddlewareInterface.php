<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface AttributeMiddlewareInterface extends MiddlewareInterface
{
    public function run(ServerRequestInterface $request, \Closure $next): mixed;
}
