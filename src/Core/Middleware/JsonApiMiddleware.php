<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
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

    public function run(Closure $callback): ResponseInterface
    {
        try {
            /** @var ResponseInterface|mixed $response */
            $response = $callback();

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
                    json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)
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
                        $response = json_decode($response, true);
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

        if ($this->app->isDebug()) {
            $data['exception'] = $e::class;
            $data['backtraces'] = BacktraceHelper::normalizeBacktraces($e->getTrace());

            foreach ($data['backtraces'] as &$datum) {
                unset($datum['pathname']);
            }

            unset($datum);

            // if (class_exists(DebuggerHelper::class)) {
            //     try {
            //         $data['debug_messages'] = (array) DebuggerHelper::getInstance()->get('debug.messages');
            //     } catch (\Exception $e) {
            //         // None
            //     }
            // }
        }

        $message = !$this->app->isDebug() ? $e->getMessage() : sprintf(
            '#%d %s - File: %s (%d)',
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $buffer = new JsonBuffer($message, $data, false, $e->getCode());
        $buffer->status = ErrorService::normalizeCode($apiException->getStatusCode());

        return (new JsonResponse($buffer))
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
