<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Windwalker\DI\Container;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The WebApplication class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WebApplication
{
    use OptionAccessTrait;

    protected Container $container;

    /**
     * WebApplication constructor.
     *
     * @param Container $container
     * @param array     $options
     */
    public function __construct(Container $container, array $options = [])
    {
        $this->container = $container;

        $this->prepareOptions(
            [],
            $options
        );
    }
}
