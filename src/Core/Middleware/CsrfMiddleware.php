<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Security\CsrfService;
use Windwalker\DI\Attributes\Service;
use Windwalker\DI\DICreateTrait;
use Windwalker\Utilities\Options\OptionAccessTrait;

/**
 * The CsrfMiddleware class.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    use OptionAccessTrait;
    use DICreateTrait;
    use AttributeMiddlewareTrait;

    #[Service]
    protected CsrfService $csrfService;

    #[Service]
    protected AppContext $app;

    /**
     * CsrfMiddleware constructor.
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function run(ServerRequestInterface $request, \Closure $next): mixed
    {
        if ($this->isExclude()) {
            return $next($request);
        }

        $methods = $this->getOption('working_methods')
            ?? [
                'post',
                'put',
                'patch',
                'delete'
            ];

        $method = strtolower($this->app->getAppRequest()->getOverrideMethod());

        if (in_array($method, $methods, true)) {
            $this->csrfService->validate(
                $this->app->getAppRequest(),
                $this->getOption('input_method'),
                $this->getOption('invalid_message'),
            );
        }

        return $next($request);
    }

    protected function isExclude(): bool
    {
        $excludes = $this->getOption('excludes');

        if (is_callable($excludes)) {
            if ($this->app->call($excludes)) {
                return true;
            }
        } elseif ($excludes !== null) {
            $excludes = (array) $excludes;

            $route = $this->app->getMatchedRoute()?->getName();

            return in_array($route, $excludes, true);
        }

        return false;
    }
}
