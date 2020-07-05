<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;
use Windwalker\DI\Annotation\AnnotationInterface;
use Windwalker\DI\Annotation\MethodAnnotationInterface;
use Windwalker\DI\Container;
use Windwalker\IO\Input;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The Method class.
 *
 * @Annotation
 *
 * @Target({"METHOD", "CLASS"})
 *
 * @since  3.5.19
 */
class Methods implements AnnotationInterface
{
    use OptionAccessTrait;

    /**
     * Method constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Container $container, $instance, \Reflector $method)
    {
        $methods = (array) $this->getOption('value');

        if ($methods === []) {
            return;
        }

        $methods = array_map('strtoupper', $methods);

        $input = $container->get(Input::class);

        if (!in_array(strtoupper($input->getMethod()), $methods, true)) {
            throw new RouteNotFoundException('Page not found');
        }
    }
}
