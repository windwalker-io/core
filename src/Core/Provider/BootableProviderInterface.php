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
 * Interface BootableProviderInterface
 *
 * @since  3.5
 */
interface BootableProviderInterface extends ServiceProviderInterface
{
    /**
     * boot
     *
     * @param Container $container
     *
     * @return  void
     */
    public function boot(Container $container);
}
