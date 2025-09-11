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
class CsrfMiddleware implements AttributeMiddlewareInterface
{
    use OptionAccessTrait;
    use DICreateTrait;
    use AttributeMiddlewareTrait;
    use RoutingExcludesTrait;

    #[Service]
    protected CsrfService $csrfService;

    /**
     * CsrfMiddleware constructor.
     */
    public function __construct(
        protected array|Closure|null $excludes = null,
        protected ?array $workingMethods = null,
        protected ?string $inputMethod = null,
        protected ?string $invalidMessage = null,
        /**
         * @deprecated  Use constructor arguments instead.
         */
        array $options = []
    ) {
        $this->options = $options;
    }

    public function run(ServerRequestInterface $request, Closure $next): mixed
    {
        if ($result = $this->isExclude()) {
            return $result === true ? $next($request) : $result;
        }

        $methods = $this->getOption('working_methods')
            ?? $this->workingMethods
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
                : $this->getOption('input_method', $this->inputMethod);

            $this->csrfService->validate(
                $this->app->getAppRequest(),
                $inputMethod,
                $this->getOption('invalid_message', $this->invalidMessage),
            );
        }

        return $next($request);
    }

    public function getExcludes(): mixed
    {
        return $this->excludes ?? $this->getOption('excludes');
    }
}
