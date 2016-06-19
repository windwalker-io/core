<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Debugger\Listener\DebuggerListener;
use Windwalker\Debugger\Provider\ProfilerProvider;
use Windwalker\DI\Container;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\DispatcherInterface;

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
	protected $name = 'debugger';

	/**
	 * initialise
	 *
	 * @return  void
	 */
	public function boot()
	{
		parent::boot();

		$this->getContainer()->getParent()->share('windwalker.debugger', $this);
	}

	/**
	 * registerProviders
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public function registerProviders(Container $container)
	{
		$container->registerServiceProvider(new ProfilerProvider);
	}

	/**
	 * registerListeners
	 *
	 * @param DispatcherInterface $dispatcher
	 *
	 * @return  void
	 */
	public function registerListeners(DispatcherInterface $dispatcher)
	{
		parent::registerListeners($dispatcher);

		$dispatcher->addListener(new DebuggerListener($this));
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
