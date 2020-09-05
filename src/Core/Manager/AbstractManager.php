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

    public function create(?string $name = null, ...$args)
    {
        $prefix = $this->getConfigPrefix();

        $name ??= $this->config->getDeep($prefix . '.default');

        if ($name === null) {
            throw new \InvalidArgumentException('Empty definition name.');
        }

        $define = $this->config->getDeep($this->getFactoryPath($name));

        if (!$define) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Definition: %s not found, the factory key is: %s',
                    $name,
                    $this->getFactoryPath($name)
                )
            );
        }

        return $this->container->newInstance($define, $args);
    }

    abstract  public function getConfigPrefix(): string;

    protected function getFactoryPath(string $name): string
    {
        return $this->getConfigPrefix() . '.factories.instances.' . $name;
    }
}
