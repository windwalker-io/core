<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Controller\Exception\ControllerDispatchException;
use Windwalker\DI\Container;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\StrNormalise;

/**
 * The ControllerDispatcher class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ControllerDispatcher
{
    /**
     * ControllerDispatcher constructor.
     *
     * @param  Container  $container
     */
    public function __construct(protected Container $container)
    {
        //
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $controller = $request->getAttribute('controller');

        if (is_string($controller)) {
            if (str_contains($controller, '::')) {
                $controller = explode('::', $controller, 2);
            } elseif (class_exists($controller)) {
                $controller = [$controller, $this->getDefaultTask($request)];
            }
        }

        if (is_array($controller)) {
            $controller = $this->prepareArrayCallable($controller);
        } else {
            $controller = fn(): mixed => $this->container->call($controller, $this->getVarsFromRequest($request));
        }

        return $this->handleResponse($controller($request));
    }

    protected function getDefaultTask(ServerRequestInterface $request): string
    {
        $task = strtolower($request->getMethod());

        if (str_contains($task, '_')) {
            $task = StrNormalise::toCamelCase($task);
        }

        return $task;
    }

    protected function prepareArrayCallable(array $handler): \Closure
    {
        if (\Windwalker\count($handler) !== 2) {
            throw new \LogicException(
                'Controller callable should be array with 2 elements, got: ' . \Windwalker\count($handler)
            );
        }

        $handler[0] = $this->container->createSharedObject($handler[0]);

        if ($handler[0] instanceof ControllerInterface) {
            return function (ServerRequestInterface $request) use ($handler): mixed {
                [$object, $task] = $handler;
                return $this->container->call(
                    [$object, 'execute'],
                    [$task, $this->getVarsFromRequest($request)]
                );
            };
        }

        if (!is_callable($handler)) {
            throw new ControllerDispatchException('Controller is not callable.');
        }

        return function (ServerRequestInterface $request) use ($handler) {
            $this->container->call($handler, $this->getVarsFromRequest($request));
        };
    }

    protected function getVarsFromRequest(ServerRequestInterface $request): array
    {
        return $request->getAttribute('vars') ?? [];
    }

    /**
     * handleResponse
     *
     * @param  mixed  $res
     *
     * @return  ResponseInterface|Response
     *
     * @throws \JsonException
     * @since  __DEPLOY_VERSION__
     */
    protected function handleResponse(mixed $res): ResponseInterface
    {
        if (!$res instanceof ResponseInterface) {
            if (is_array($res) && is_object($res)) {
                return Response::fromString(json_encode($res, JSON_THROW_ON_ERROR));
            }

            return Response::fromString((string) $res);
        }

        return $res;
    }
}
