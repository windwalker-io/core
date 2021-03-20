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
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;

/**
 * The DelegatingController class.
 */
class DelegatingController implements ControllerInterface
{
    protected array $viewMap = [];

    /**
     * DelegatingController constructor.
     *
     * @param  Container  $container
     * @param  object     $controller
     */
    public function __construct(protected Container $container, protected object $controller)
    {
        //
    }

    /**
     * execute
     *
     * @param  string  $task
     * @param  array   $args
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function execute(string $task, array $args = []): mixed
    {
        if (isset($args['view'])) {
            $args['view'] = $this->viewMap[$args['view']] ?? $args['view'];
        }

        if (!method_exists($this->controller, $task)) {
            if ($task !== 'index') {
                throw new \LogicException(
                    sprintf(
                        'Method: %s::%s() not found.',
                        $this->controller::class,
                        $task
                    )
                );
            }

            return $this->container->call([$this, 'renderView'], $args);
        }

        return $this->container->call([$this->controller, $task], $args);
    }

    public function renderView(string $view, AppContext $app): string
    {
        /** @var View $vm */
        $vm = $app->make($view);

        return $vm->render();
    }

    /**
     * @return array
     */
    public function getViewMap(): array
    {
        return $this->viewMap;
    }

    /**
     * @param  array  $viewMap
     *
     * @return  static  Return self to support chaining.
     */
    public function setViewMap(array $viewMap): static
    {
        $this->viewMap = $viewMap;

        return $this;
    }
}
