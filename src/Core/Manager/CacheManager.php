<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Cache\CachePool;

/**
 * The CacheManager class.
 *
 * @method CachePool get(?string $name = null, ...$args)
 * @method CachePool create(?string $name = null, ...$args)
 */
class CacheManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'cache';
    }

    /**
     * getDefaultFactory
     *
     * @param  string  $name
     * @param  mixed   ...$args
     *
     * @return  mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }
}
