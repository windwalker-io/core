<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Console;

use Windwalker\Application\Application;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Provider\CacheProvider;
use Windwalker\Core\Provider\ConsoleProvider;
use Windwalker\Core\Provider\DatabaseProvider;
use Windwalker\Core\Provider\EventProvider;
use Windwalker\Core\Provider\LanguageProvider;
use Windwalker\Core\Provider\SystemProvider;
use Windwalker\DI\Container;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;

/**
 * The Console class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class WindwalkerConsole extends \Windwalker\Console\Console implements DispatcherAwareInterface
{
	/**
	 * The Console title.
	 *
	 * @var  string
	 */
	protected $name = 'Windwalker Console';

	/**
	 * Version of this application.
	 *
	 * @var string
	 */
	protected $version = '2.0';

	/**
	 * The DI container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Property config.
	 *
	 * @var Registry
	 */
	public $config;

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		$this->loadConfiguration($this->config);

		$this->registerRootCommand();

		$this->container = $this->container = Ioc::getContainer();

		$this->container->registerServiceProvider(new SystemProvider($this))
			->registerServiceProvider(new ConsoleProvider($this));

		static::registerProviders($this->container);

		PackageHelper::registerPackages(Application::getPackages(), $this, $this->container);
	}

	/**
	 * registerProviders
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public static function registerProviders(Container $container)
	{
		$container
			->registerServiceProvider(new EventProvider)
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new LanguageProvider)
			->registerServiceProvider(new CacheProvider);
	}

	/**
	 * Execute the application.
	 *
	 * @return  int  The Unix Console/Shell exit code.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function execute()
	{
		$this->triggerEvent('onBeforeExecute');

		// Perform application routines.
		$exitCode = $this->doExecute();

		$this->triggerEvent('onAfterExecute');

		return $exitCode;
	}

	/**
	 * loadConfiguration
	 *
	 * @param Registry $config
	 *
	 * @throws  \RuntimeException
	 * @return  void
	 */
	protected function loadConfiguration($config)
	{
		$file = WINDWALKER_ETC . '/config.yml';

		if (!is_file($file))
		{
			exit('Please copy config.dist.yml to config.yml');
		}

		$config->loadFile($file, 'yaml');
	}

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function triggerEvent($event)
	{
		/** @var \Windwalker\Event\Dispatcher $dispatcher */
		$dispatcher = $this->container->get('system.dispatcher');

		$dispatcher->triggerEvent($event);

		return $this;
	}
}
 