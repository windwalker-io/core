<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

/**
 * The AbstractConfigServiceProvider class.
 *
 * @since  2.0
 */
abstract class AbstractConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * Property config.
     *
     * @var \Windwalker\Structure\Structure
     */
    protected $config;

    /**
     * Class init.
     *
     * @param $config
     */
    public function __construct(Structure $config = null)
    {
        $this->config = $config ?: new Structure;
    }
}
