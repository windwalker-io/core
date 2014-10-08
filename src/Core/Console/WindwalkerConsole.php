<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Console;
use Windwalker\Core\Console\Descriptor\CommandDescriptor;
use Windwalker\Core\Migration\Command\PhinxCommand;
use Windwalker\Core\Seeder\Command\SeedCommand;
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
class WindwalkerConsole extends Console implements DispatcherAwareInterface
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
		// Load config
		$this->loadConfiguration($this->config);

		// Register the root command to let packages can add child commands
		$this->registerRootCommand();

		// Init container and register system providers
		$this->container = Ioc::getContainer();

		$this->container->registerServiceProvider(new SystemProvider($this))
			->registerServiceProvider(new ConsoleProvider($this));

		// Register custom providers
		$this->registerProviders($this->container);

		// Register commands
		$this->registerCommands();

		// Load packages
		PackageHelper::registerPackages($this->getPackages(), $this, $this->container);
	}

	public function registerCommands()
	{
		$this->addCommand(new PhinxCommand);
		$this->addCommand(new SeedCommand);
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
		$container
			->registerServiceProvider(new EventProvider)
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new LanguageProvider)
			->registerServiceProvider(new CacheProvider);
	}

	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public function getPackages()
	{
		return array();
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
 