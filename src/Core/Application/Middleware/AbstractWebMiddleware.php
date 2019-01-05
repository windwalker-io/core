<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Middleware\Psr7InvokableInterface;

/**
 * The AbstractApplicationMiddleware class.
 *
 * @since  3.0
 */
abstract class AbstractWebMiddleware implements Psr7InvokableInterface
{
    /**
     * Property app.
     *
     * @Inject()
     *
     * @var  WebApplication
     */
    protected $app;

    /**
     * Property package.
     *
     * @Inject()
     *
     * @var  AbstractPackage
     */
    protected $package;
}
