<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use LogicException;
use ReflectionException;
use Throwable;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Error\ErrorLogHandler;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Service\LoggerService;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;

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
     * @param  AppContext  $app
     * @param  object      $controller
     */
    public function __construct(
        protected AppContext $app,
        protected object $controller
    ) {
        //
    }

    /**
     * execute
     *
     * @param  string  $task
     * @param  array   $args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function execute(string $task, array $args = []): mixed
    {
        if (isset($args['view'])) {
            $args['view'] = $this->viewMap[$args['view']] ?? $args['view'];
        }

        // Prepare Module
        // Todo: Remove module system
        if ($this->module) {
            /** @var ModuleInterface $module */
            $module = $this->app->make($this->module);

            $this->app->getContainer()
                ->share($this->module, $module)
                ->alias(ModuleInterface::class, $this->module);

            $args[AppState::class] = $module->getState();
        }

        $handler = [$this->controller, $task];

        if (!method_exists($this->controller, $task)) {
            if ($task !== 'index') {
                throw new LogicException(
                    sprintf(
                        'Method: %s::%s() not found.',
                        $this->controller::class,
                        $task
                    )
                );
            }

            $handler = [$this, 'renderView'];
        }

        try {
            $res = $this->app->call($handler, $args);

            // Todo: Remove module system
            if ($this->module) {
                $this->app->getContainer()->remove($this->module)
                    ->removeAlias(ModuleInterface::class);
            }

            return $res;
        } catch (ValidateFailException $e) {
            if ($this->app->isDebug()) {
                throw $e;
            }

            $this->app->addMessage($e->getMessage(), 'warning');
            $nav = $this->app->service(Navigator::class);

            return $nav->back();
        } catch (Throwable $e) {
            $this->logError($e);

            if (
                $this->app->isDebug()
                || strtoupper($this->app->getRequestMethod()) === 'GET'
                || $this->app->getAppRequest()->isAcceptJson()
            ) {
                throw $e;
            }

            $this->app->addMessage($e->getMessage(), 'warning');
            $nav = $this->app->service(Navigator::class);

            return $nav->back();
        }
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

    /**
     * @param  Throwable  $e
     *
     * @return  void
     *
     * @throws ReflectionException
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    protected function logError(Throwable $e): void
    {
        $message = ErrorLogHandler::handleExceptionLogText($e, $this->app->path('@root'));

        $this->app->service(LoggerService::class)
            ->error(
                $this->app->config('error.log_channel') ?? 'error',
                $message,
                ['exception' => $e]
            );
    }
}
