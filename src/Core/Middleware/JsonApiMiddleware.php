<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Windwalker\Core\Http\Exception\ApiException;
use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\DICreateTrait;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Session\Session;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Reflection\BacktraceHelper;

/**
 * The JsonApiMiddleware class.
 */
class JsonApiMiddleware extends JsonResponseMiddleware
{
    use DICreateTrait;

    public function run(ServerRequestInterface $request, Closure $next): mixed
    {
        try {
            /** @var ResponseInterface|mixed $response */
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
            }

            $message = $this->getMessage();

            if ($response instanceof JsonResponse) {
                $buffer = new JsonBuffer(
                    $message,
                    json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR)
                );

                $buffer->status = $response->getStatusCode();

                $response = new JsonResponse(
                    $buffer,
                    $response->getStatusCode(),
                    $response->getHeaders()
                );
            } else {
                if ($response instanceof ResponseInterface) {
                    $response = (string) $response->getBody();

                    if (is_json($response)) {
                        $response = json_decode($response, false);
                    }
                }

                $response = new JsonResponse(
                    new JsonBuffer(
                        $message,
                        $response
                    ),
                    200
                );
            }

            return $response;
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException(Throwable $e): Response
    {
        $apiException = ApiException::wrap($e);
        $e = $apiException->getPrevious() ?? $apiException;

        $data = [];
        $backtraces = null;

        if ($this->app->isDebug()) {
            $data['exception'] = $e::class;
            $backtraces = BacktraceHelper::normalizeBacktraces($e->getTrace(), $this->app->path('@root'));

            // Add last caller
            array_unshift(
                $backtraces,
                [
                    'file' => BacktraceHelper::replaceRoot($e->getFile(), $this->app->path('@root'))
                        . ':' . $e->getLine(),
                    'function' => $e->getMessage(),
                ]
            );

            $data['backtraces'] = $backtraces = Arr::mapWithKeys(
                $backtraces,
                static fn($v, $k) => yield "#{$k} {$v['file']}" => $v['function']
            );

            // foreach ($data['backtraces'] as &$datum) {
            //     unset($datum['pathname']);
            // }

            // unset($datum);

            // if (class_exists(DebuggerHelper::class)) {
            //     try {
            //         $data['debug_messages'] = (array) DebuggerHelper::getInstance()->get('debug.messages');
            //     } catch (\Exception $e) {
            //         // None
            //     }
            // }

            $data = array_merge($data, $apiException->debugData);
        }

        $data = array_merge($data, $apiException->data);

        $message = !$this->app->isDebug() ? $e->getMessage() : sprintf(
            '#%d %s - File: %s (%d)',
            $apiException->getErrCode() ?: $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $buffer = new JsonBuffer($message, $data, false, $apiException->getErrCode());
        $buffer->status = ErrorService::normalizeCode($apiException->getStatusCode());

        return new JsonResponse($buffer)
            ->withStatus(
                ErrorService::normalizeCode($apiException->getStatusCode())
            );
    }

    /**
     * getMessage
     *
     * @return  string
     * @throws DefinitionException
     */
    protected function getMessage(): string
    {
        if (!class_exists(Session::class)) {
            return '';
        }

        $msg = $this->app->service(Session::class)->getFlashBag()->all();

        return implode("\n", Arr::collapse($msg));
    }
}
