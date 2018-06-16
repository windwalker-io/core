<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger;

use Windwalker\Core\Package\AbstractPackage;

define('WINDWALKER_DEBUGGER_ROOT', __DIR__);

/**
 * The WebProfilerPackage class.
 *
 * @since  2.1.1
 */
class DebuggerPackage extends AbstractPackage
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = '_debugger';

    /**
     * initialise
     *
     * @return  void
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function boot()
    {
        parent::boot();

        $this->getContainer()->getParent()->share('windwalker.debugger', $this);
    }

    /**
     * enableConsole
     *
     * @return  static
     */
    public function enableConsole()
    {
        $this->config->set('console.enabled', 1);

        return $this;
    }

    /**
     * disableConsole
     *
     * @return  static
     */
    public function disableConsole()
    {
        $this->config->set('console.enabled', 0);

        return $this;
    }
}
