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

/**
 * The ControllerDispatcher class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ControllerDispatcher
{
    protected Container $container;

    /**
     * ControllerDispatcher constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch(ServerRequestInterface $request)
    {
        $controller = $request->getAttribute('controller');

        if (is_string($controller)) {
            if (str_contains($controller, '::')) {
                $controller = explode('::', $controller, 2);
            } elseif (class_exists($controller)) {
                $controller = [$controller, 'handle'];
            }
        }

        if (is_array($controller)) {
            $controller = $this->prepareArrayCallable($controller);
        }

        if (!is_callable($controller)) {
            throw new ControllerDispatchException('Controller is not callable.');
        }

        $res = $this->container->call($controller, $this->getVarsFromRequest($request));

        return $this->handleResponse($res);
    }

    protected function prepareArrayCallable(array $handler): callable
    {
        if (\Windwalker\count($handler) !== 2) {
            throw new \LogicException(
                'Controller callable should be array with 2 elements, got: ' . \Windwalker\count($handler)
            );
        }

        $handler[0] = $this->container->createSharedObject($handler[0]);

        return $handler;
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
    protected function handleResponse($res): ResponseInterface|Response
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
