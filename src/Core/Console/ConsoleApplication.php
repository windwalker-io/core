<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Composer\InstalledVersions;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Application as SymfonyApp;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Utilities\Arr;

/**
 * The ConsoleApplication class.
 */
class ConsoleApplication extends SymfonyApp implements ApplicationInterface
{
    use ApplicationTrait;

    protected bool $booted = false;

    /**
     * ConsoleApplication constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct(
            'Windwalker Console',
            InstalledVersions::getPrettyVersion('windwalker/core')
        );
    }

    /**
     * boot
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Prepare child
        $container = $this->getContainer();
        $container->registerServiceProvider(new AppProvider($this));

        $container->registerByConfig($this->config('di') ?? []);

        foreach ($this->config as $service => $config) {
            if (!is_array($config)) {
                throw new \LogicException("Config: '{$service}' must be array");
            }

            $container->registerByConfig($config ?: []);
        }

        $commands = Arr::flatten($commands = (array) $this->config('commands'), ':');

        // Commands
        $this->setCommandLoader(
            new ContainerCommandLoader(
                $container,
                $commands
            )
        );

        foreach ($commands as $name => $command) {
            $container->prepareSharedObject($command);
        }

        $this->booted = true;
    }

    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function close(mixed $return = 0): void
    {
        exit((int) $return);
    }
}
