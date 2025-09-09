<?php

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Closure;
use LogicException;
use ReflectionAttribute;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Attributes\TaskMapping;
use Windwalker\Core\Controller\Exception\ControllerDispatchException;
use Windwalker\Core\Events\Web\AfterControllerDispatchEvent;
use Windwalker\Core\Events\Web\BeforeControllerDispatchEvent;
use Windwalker\Core\Http\OutsideRedirectResponse;
use Windwalker\Core\Router\Navigator;
use Windwalker\DI\Container;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Utilities\StrNormalize;

/**
 * The ControllerDispatcher class.
 *
 * @since  4.0
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

    public function dispatch(AppContextInterface $app, array $args = []): mixed
    {
        $controller = $app->getController();

        $event = $app->emit(
            new BeforeControllerDispatchEvent(
                controller: $controller,
                app: $app
            ),
        );

        $controller = $event->controller;

        if ($controller === null) {
            throw new LogicException(
                'Controller not found, please set "controller" as a callable to :' . $app::class
            );
        }

        if (is_string($controller)) {
            if (str_contains($controller, '::')) {
                $controller = explode('::', $controller, 2);
            } elseif (class_exists($controller)) {
                $controller = [$controller, $this->getDefaultTask($app)];
            }
        }

        if (is_array($controller)) {
            $controller = $this->prepareArrayCallable($controller, $app, $args);
        } else {
            $controller = fn(AppContextInterface $app): mixed => $this->container->call(
                $controller,
                [
                    ...$app->getUrlVars(),
                    ...$args,
                ]
            );
        }

        $response = $this->handleResponse($controller($app), $app);

        $event = $app->emit(
            new AfterControllerDispatchEvent(
                app: $app,
                response: $response
            ),
        );

        return $event->response;
    }

    protected function getDefaultTask(AppContextInterface $app): string
    {
        $task = strtolower($app->getRequestMethod());

        $map = [
            'get' => 'index',
            'post' => 'save',
            'put' => 'save',
            'patch' => 'save',
            'delete' => 'delete',
        ];

        $task = $map[$task] ?? $task;

        if (str_contains($task, '_')) {
            $task = StrNormalize::toCamelCase($task);
        }

        return $task;
    }

    protected function prepareArrayCallable(array $handler, AppContextInterface $app, array $args = []): Closure
    {
        if (\Windwalker\count($handler) !== 2) {
            throw new LogicException(
                'Controller callable should be array with 2 elements, got: ' . \Windwalker\count($handler)
            );
        }

        $class = $handler[0];

        $handler[1] = $this->processTaskMapping($class, $handler[1], $app);

        $handler[0] = $this->container->createObject($class);

        if ($handler[0] instanceof ControllerInterface) {
            return function (AppContextInterface $app) use ($args, $handler): mixed {
                [$object, $task] = $handler;

                return $this->container->call(
                    [$object, 'execute'],
                    [$task, [...$app->getUrlVars(), ...$args]]
                );
            };
        }

        if (!is_callable($handler)) {
            throw new ControllerDispatchException('Controller is not callable.');
        }

        return function (AppContextInterface $app) use ($args, $handler) {
            $this->container->call($handler, [...$app->getUrlVars(), ...$args]);
        };
    }

    protected function processTaskMapping(string $class, ?string $task, AppContextInterface $app): ?string
    {
        $mapping = AttributesAccessor::getFirstAttributeInstance(
            $class,
            TaskMapping::class,
            ReflectionAttribute::IS_INSTANCEOF
        );

        return $mapping?->processTask($app->getRequestMethod(), $task) ?? $task;
    }

    /**
     * @param  mixed  $response
     * @param  AppContextInterface  $app
     *
     * @return  mixed
     */
    public function handleResponse(mixed $response, AppContextInterface $app): mixed
    {
        if ($response instanceof RedirectResponse && !$response instanceof OutsideRedirectResponse) {
            $nav = $app->retrieve(Navigator::class);

            $url = $nav->validateRedirectUrl($response->getHeaderLine('Location'));

            $response = $response->withHeader('Location', $url);
        }

        return $response;
    }
}
