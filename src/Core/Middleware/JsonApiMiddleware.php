<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Windwalker\Core\Response\Buffer\AbstractBuffer;
use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Core\Service\ErrorService;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Session\Session;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Reflection\BacktraceHelper;

/**
 * The JsonApiMiddleware class.
 */
class JsonApiMiddleware extends JsonResponseMiddleware
{
    public function run(\Closure $callback): ResponseInterface
    {
        try {
            /** @var ResponseInterface $response */
            $response = $callback();

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
                $response = new JsonResponse(
                    new JsonBuffer(
                        $message,
                        $response
                    ),
                    200
                );
            }

            return $response;
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    protected function handleException(\Throwable $e): Response
    {
        $data = [];

        if ($this->app->isDebug()) {
            $data['exception']  = $e::class;
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

        $message = $this->app->isDebug() ? $e->getMessage() : sprintf(
            '#%d %s - File: %s (%d)',
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $buffer         = new JsonBuffer($message, $data, false, $e->getCode());
        $buffer->status = ErrorService::normalizeCode($e->getCode());

        return (new JsonResponse($buffer))
            ->withStatus(
                ErrorService::normalizeCode($e->getCode())
            );
    }

    /**
     * getMessage
     *
     * @return  string
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    protected function getMessage(): string
    {
        $msg = $this->app->service(Session::class)->getFlashBag()->all();

        return implode("\n", Arr::collapse($msg));
    }
}
