<?php

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use ReflectionException;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;

/**
 * The Controller class.
 */
class Controller implements ControllerInterface
{
    use ContainerAwareTrait;

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
     * @param  string  $task
     * @param  array   $args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function execute(string $task, array $args = []): mixed
    {
        return $this->getContainer()->call([$this, $task], $args);
    }
}
