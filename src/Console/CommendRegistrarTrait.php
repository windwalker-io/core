<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console;

use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Windwalker\DI\Container;

use function Windwalker\DI\create;

/**
 * Trait CommendRegistrarTrait
 */
trait CommendRegistrarTrait
{
    public function registerCommands(array $commands): void
    {
        $commandsMap = [];

        $container = $this->getContainer();

        foreach ($commands as $name => $command) {
            if (is_string($command)) {
                // Handle class command
                if (class_exists($command)) {
                    $container->bind($command, create($command, name: $name));
                    $commandsMap[$name] = $command;
                    continue;
                }

                if (is_file($command)) {
                    $command = include $command;
                }
            }

            // Object and closure
            if (is_object($command)) {
                $container->set(
                    $id = 'command:' . $name,
                    function (Container $container) use (&$name, $command) {
                        /** @var CommandWrapper $cmd */
                        $cmd = $container->getAttributesResolver()->decorateObject($command);

                        if ($cmd->getName() !== CommandWrapper::TEMP_NAME) {
                            // If CommandWrapper name set, use command name
                            $name = $cmd->getName();
                        } else {
                            // Otherwise use key as name.
                            $cmd->setName($name);
                        }

                        return $cmd;
                    }
                );

                $commandsMap[$name] = $id;
            }
        }

        $this->setCommandLoader(
            new ContainerCommandLoader(
                $container,
                $commandsMap
            )
        );
    }
}
