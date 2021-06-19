<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Console\Color;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ConsoleProvider class.
 */
class ConsoleProvider implements ServiceProviderInterface
{
    /**
     * ConsoleProvider constructor.
     *
     * @param  ConsoleApplication  $app
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        // class_alias(Color::class, \Symfony\Component\Console\Color::class);

        $container->mergeParameters(
            'commands',
            require __DIR__ . '/../../../resources/registry/commands.php'
        );

        $container->mergeParameters(
            'generator.commands',
            require __DIR__ . '/../../../resources/registry/generator.php'
        );
    }
}
