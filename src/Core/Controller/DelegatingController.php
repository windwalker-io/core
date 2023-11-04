<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use LogicException;
use ReflectionException;
use Throwable;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Service\LoggerService;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\WebSocket\Application\WsApplicationInterface;

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
     * @param  AppContextInterface  $app
     * @param  object               $controller
     */
    public function __construct(
        protected AppContextInterface $app,
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
     * @throws Throwable
     */
    public function execute(string $task, array $args = []): mixed
    {
        if (isset($args['view'])) {
            $args['view'] = $this->viewMap[$args['view']] ?? $args['view'];
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

        return $this->app->call($handler, $args);
    }

    public function renderView(string $view, AppContext $app): mixed
    {
        return $app->renderView(
            $view,
            options: ['is_child' => false]
        );
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
        // Do not log 40x errors.
        if ($e->getCode() >= 400 && $e->getCode() < 500) {
            return;
        }

        // $message = ErrorLogHandler::handleExceptionLogText($e, $this->app->path('@root'));

        $this->app->service(LoggerService::class)
            ->error(
                $this->app->config('error.log_channel') ?? 'error',
                $e->getMessage(),
                ['exception' => $e]
            );
    }
}
