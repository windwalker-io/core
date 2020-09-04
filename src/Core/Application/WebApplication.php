<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Relay\Relay;
use Windwalker\DI\Container;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The WebApplication class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WebApplication
{
    protected Container $container;

    /**
     * WebApplication constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    public function loadConfig($source, ?string $format = null, array $options = []): void
    {
        $this->getContainer()->loadParameters($source, $format, $options);
    }

    public function execute(ServerRequestInterface $request)
    {
        $queue = [
            \Closure::fromCallable([$this, 'doExecute'])
        ];

        $relay = new Relay($queue);

        return $relay->handle($request);
    }

    protected function doExecute(): ResponseInterface
    {
        return Response::fromString('Hello');
    }
}
