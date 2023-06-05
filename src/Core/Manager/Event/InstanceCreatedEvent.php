<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager\Event;

use Windwalker\Event\AbstractEvent;

/**
 * The InstanceCreatedEvent class.
 */
class InstanceCreatedEvent extends AbstractEvent
{
    protected object $instance;

    protected string $instanceName = '';

    protected array $args = [];

    /**
     * @return object
     */
    public function getInstance(): object
    {
        return $this->instance;
    }

    /**
     * @param  object  $instance
     *
     * @return  static  Return self to support chaining.
     */
    public function setInstance(object $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * @param  string  $instanceName
     *
     * @return  static  Return self to support chaining.
     */
    public function setInstanceName(string $instanceName): static
    {
        $this->instanceName = $instanceName;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param  array  $args
     *
     * @return  static  Return self to support chaining.
     */
    public function setArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }
}
