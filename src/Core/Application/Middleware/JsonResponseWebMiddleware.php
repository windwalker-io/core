<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Middleware\MiddlewareInterface;

/**
 * The JsonErrorMiddleware class.
 *
 * @since  3.2
 */
class JsonResponseWebMiddleware extends AbstractWebMiddleware
{
    /**
     * Middleware logic to be invoked.
     *
     * @param   Request                      $request  The request.
     * @param   Response                     $response The response.
     * @param   callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     * @throws \UnexpectedValueException
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        if (class_exists(DebuggerHelper::class)) {
            DebuggerHelper::disableConsole();
        }

        // Replace Default Error handler
        $error = $this->app->container->get('error.handler');
        $error->addHandler(function ($exception) {
            /** @var \Exception|\Throwable $exception */
            $message = !WINDWALKER_DEBUG ? $exception->getMessage() : sprintf(
                '#%d %s - File: %s (%d)',
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );

            $this->app
                ->getServer()
                ->getOutput()
                ->respond(
                    new JsonResponse(['error' => $message], $exception->getCode())
                );
        }, 'default');

        /** @var Response $response */
        $response = $next($request, $response);

        if (!$response instanceof JsonResponse) {
            $response = new JsonResponse(
                $response->getBody()->__toString(),
                $response->getStatusCode(),
                $response->getHeaders()
            );
        }

        return $response;
    }
}
