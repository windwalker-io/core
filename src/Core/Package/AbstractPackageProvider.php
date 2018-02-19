<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The PackageProvider class.
 *
 * @since  2.0
 */
abstract class AbstractPackageProvider implements ServiceProviderInterface
{
    /**
     * Property package.
     *
     * @var AbstractPackage
     */
    protected $package;

    /**
     * Class init.
     *
     * @param AbstractPackage $package
     */
    public function __construct(AbstractPackage $package)
    {
        $this->package = $package;
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
    }
}
