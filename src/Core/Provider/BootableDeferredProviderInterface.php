<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * Interface BootableDeferredProviderInterface
 *
 * @since  3.5
 */
interface BootableDeferredProviderInterface extends ServiceProviderInterface
{
    /**
     * boot
     *
     * @param Container $container
     *
     * @return  void
     */
    public function bootDeferred(Container $container);
}
