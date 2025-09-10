<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Error\ErrorHandlerInterface;
use Windwalker\Core\Error\ErrorLogHandler;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\DICreateTrait;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Http\Response\RedirectResponse;

use function Windwalker\response;

/**
 * The JsonResponseMiddleware class.
 */
class JsonResponseMiddleware implements AttributeMiddlewareInterface
{
    use DICreateTrait;
    use AttributeMiddlewareTrait;

    /**
     * JsonResponseMiddleware constructor.
     */
    public function __construct(protected AppContext $app)
    {
    }

    public function run(ServerRequestInterface $request, Closure $next): mixed
    {
        try {
            $response = $next($request);

            if ($response instanceof ResponseInterface) {
                // Allow redirect
                if ($response instanceof RedirectResponse || $response->hasHeader('location')) {
                    return $response;
                }

                // Allow override non-json response
                if (
                    $response->hasHeader('Content-Type')
                    && !str_contains($response->getHeaderLine('Content-Type'), 'application/json')
                ) {
                    return $response;
                }

                return static::toJsonResponse($response);
            }

            return new JsonResponse(
                $response,
                200
            );
        } catch (Throwable $e) {
            $this->logError($e);

            return response()->json(
                [
                    'error' => !$this->app->isDebug() ? $e->getMessage() : sprintf(
                        '#%d %s - File: %s (%d)',
                        $e->getCode(),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    ),
                    'status' => ErrorService::normalizeCode($e->getCode()),
                    'code' => $e->getCode(),
                ]
            );
        }
    }

    protected static function toJsonResponse(ResponseInterface $response): JsonResponse
    {
        return JsonResponse::from($response);
    }

    protected function logError(\Throwable $e): void
    {
        $this->app->retrieve(ErrorService::class)->logException($e);
    }
}
