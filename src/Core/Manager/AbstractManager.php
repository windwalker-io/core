<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\DefinitionInterface;
use Windwalker\Utilities\Classes\ObjectBuilderAwareTrait;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The AnstractManager class.
 */
abstract class AbstractManager
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * AbstractManager constructor.
     *
     * @param  Config     $config
     * @param  Container  $container
     */
    public function __construct(Config $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function create(?string $name = null)
    {
        $name ??= $this->getConfiguration('default') ?? null;

        if ($name === null) {
            throw new \InvalidArgumentException('Empty definition name.');
        }

        $define = $this->getDefinition($name);

        if (!$define) {
            throw new \InvalidArgumentException('Definition: ' . $name . ' not found.');
        }

        return $this->container->newInstance($define);
    }

    abstract public function getConfiguration(string $name);

    /**
     * getDefinition
     *
     * @param  string  $name
     *
     * @return  string|callable|DefinitionInterface
     */
    abstract public function getDefinition(string $name): string|callable|DefinitionInterface;
}
