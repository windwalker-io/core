<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Closure;
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

    public function run(ServerRequestInterface $request, Closure $next): mixed
    {
        if ($this->isExclude()) {
            return $next($request);
        }

        $methods = $this->getOption('working_methods')
            ?? [
                'post',
                'put',
                'patch',
                'delete',
            ];

        $method = strtolower($this->app->getRequestMethod());

        if (in_array($method, $methods, true)) {
            // If is api call, we only allow CSRF at header or query values
            // Otherwise you can set input_method as false to only allow header.
            $inputMethod = $this->app->isApiCall()
                ? 'GET'
                : $this->getOption('input_method');

            $this->csrfService->validate(
                $this->app->getAppRequest(),
                $inputMethod,
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
