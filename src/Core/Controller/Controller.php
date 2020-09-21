<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;

/**
 * The Controller class.
 */
class Controller implements ControllerInterface
{
    use ContainerAwareTrait;

    protected ServerRequestInterface $request;

    /**
     * Controller constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * execute
     *
     * @param  ServerRequestInterface  $request
     *
     * @param  string                  $task
     *
     * @return  mixed|ResponseInterface
     * @throws \ReflectionException
     */
    public function execute(ServerRequestInterface $request, string $task)
    {
        $this->request = $request;
        $attributes = $request->getAttributes();

        return $this->getContainer()->call([$this, $task], $attributes);
    }
}
