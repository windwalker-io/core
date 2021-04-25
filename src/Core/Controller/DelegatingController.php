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
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;

use function Windwalker\DI\create;

/**
 * The DelegatingController class.
 */
class DelegatingController implements ControllerInterface
{
    protected array $viewMap = [];

    protected ?string $module = null;

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

        // Prepare Module
        if ($this->module) {
            /** @var ModuleInterface $module */
            $module = $this->container->newInstance($this->module);

            $this->container->share($this->module, $module)
                ->alias(ModuleInterface::class, $this->module);

            $args[AppState::class] = $module->getState();
        }

        $handler = [$this->controller, $task];

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

            $handler = [$this, 'renderView'];
        }

        $res = $this->container->call($handler, $args);

        if ($this->module) {
            $this->container->remove($this->module)
                ->removeAlias(ModuleInterface::class);
        }

        return $res;
    }

    public function renderView(string $view, AppContext $app): mixed
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

    /**
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    /**
     * @param  string|null  $module
     *
     * @return  static  Return self to support chaining.
     */
    public function setModule(?string $module): static
    {
        $this->module = $module;

        return $this;
    }
}
