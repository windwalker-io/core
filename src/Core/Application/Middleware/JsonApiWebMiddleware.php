<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Error\ErrorManager;
use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Arr;

/**
 * The JsonApiWebMiddleware class.
 *
 * @since  3.5
 */
class JsonApiWebMiddleware extends AbstractWebMiddleware
{
    /**
     * Middleware logic to be invoked.
     *
     * @param   Request                      $request  The request.
     * @param   Response                     $response The response.
     * @param   callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        if (class_exists(DebuggerHelper::class)) {
            DebuggerHelper::disableConsole();
        }

        // Replace Default Error handler
        $this->app->service(ErrorManager::class)
            ->addHandler(function ($exception) {
                $this->app
                    ->getServer()
                    ->getOutput()
                    ->respond($this->handleException($exception));
            }, 'default');

        try {
            /** @var Response $response */
            $response = $next($request, $response);

            $message = $this->getMessage();

            if (!$response instanceof JsonResponse) {
                $response = new JsonResponse(
                    new JsonBuffer($message, json_decode($response->getBody()->__toString(), true)),
                    $response->getStatusCode(),
                    $response->getHeaders()
                );
            }

            return $response;
        } catch (\Exception $e) {
            return $this->handleException($e);
        } catch (\Throwable $t) {
            return $this->handleException(new \ErrorException(
                $t->getMessage(),
                $t->getCode(),
                E_ERROR,
                $t->getFile(),
                $t->getLine(),
                $t
            ));
        }
    }

    /**
     * handleException
     *
     * @param \Exception $e
     *
     * @return  Response
     * @throws \InvalidArgumentException
     */
    protected function handleException(\Exception $e): Response
    {
        $data = [];

        if ($this->app->get('system.debug')) {
            $data['exception'] = get_class($e);
            $data['backtrace'] = BacktraceHelper::normalizeBacktraces($e->getTrace());

            foreach ($data['backtrace'] as &$datum) {
                unset($datum['pathname']);
            }

            if (class_exists(DebuggerHelper::class)) {
                try {
                    $data['debug_messages'] = (array) DebuggerHelper::getInstance()->get('debug.messages');
                } catch (\Exception $e) {
                    // None
                }
            }
        }

        $message = !WINDWALKER_DEBUG ? $e->getMessage() : sprintf(
            '#%d %s - File: %s (%d)',
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        return (new JsonResponse(new JsonBuffer($message, $data, false, $e->getCode())))
            ->withStatus(
                ErrorManager::normalizeCode($e->getCode()),
                ErrorManager::normalizeMessage($e->getMessage())
            );
    }

    /**
     * getMessage
     *
     * @return  string
     */
    protected function getMessage(): string
    {
        $msg = $this->app->session->getFlashBag()->takeAll();
        $msg = implode("\n", Arr::flatten($msg));

        return $msg;
    }
}
