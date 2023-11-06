<?php

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Closure;
use LogicException;
use ReflectionAttribute;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Attributes\TaskMapping;
use Windwalker\Core\Controller\Exception\ControllerDispatchException;
use Windwalker\Core\Events\Web\AfterControllerDispatchEvent;
use Windwalker\Core\Events\Web\BeforeControllerDispatchEvent;
use Windwalker\Core\Http\AppRequest;
use Windwalker\DI\Container;
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

    public function dispatch(AppContextInterface $app): mixed
    {
        $controller = $app->getController();

        $event = $app->emit(
            BeforeControllerDispatchEvent::class,
            compact('app', 'controller')
        );

        $controller = $event->getController();

        if ($controller === null) {
            throw new LogicException(
                sprintf(
                    'Controller not found, please set "controller" as a callable to :' . $app::class
                )
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
            $controller = $this->prepareArrayCallable($controller, $app);
        } else {
            $controller = fn(AppContextInterface $app): mixed => $this->container->call(
                $controller,
                $app->getUrlVars()
            );
        }

        $response = $controller($app);

        $event = $app->emit(
            AfterControllerDispatchEvent::class,
            compact('app', 'response')
        );

        return $event->getResponse();
    }

    protected function getDefaultTask(AppContext $app): string
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

    protected function prepareArrayCallable(array $handler, AppContextInterface $app): Closure
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
            return function (AppContextInterface $app) use ($handler): mixed {
                [$object, $task] = $handler;

                return $this->container->call(
                    [$object, 'execute'],
                    [$task, $app->getUrlVars()]
                );
            };
        }

        if (!is_callable($handler)) {
            throw new ControllerDispatchException('Controller is not callable.');
        }

        return function (AppContextInterface $app) use ($handler) {
            $this->container->call($handler, $app->getUrlVars());
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
}
