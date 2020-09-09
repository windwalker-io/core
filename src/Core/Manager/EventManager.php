<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Event\EventEmitter;

/**
 * The EventManager class.
 *
 * @method EventEmitter get(?string $name = null, ...$args)
 */
class EventManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'events';
    }

    /**
     * create
     *
     * @param  string|null  $name
     * @param  mixed        ...$args
     *
     * @return  EventEmitter
     */
    public function create(?string $name = null, ...$args)
    {
        /** @var EventEmitter $dispatcher */
        $dispatcher = parent::create($name, $args);

        if ($name !== 'default' && $name !== null) {
            $dispatcher->registerDealer($this->create());
        }

        return $dispatcher;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFactory(string $name, ...$args)
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }
}
