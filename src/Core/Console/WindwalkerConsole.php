<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Console;
use Windwalker\Console\IO\IOInterface;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Migration\Command\MigrationCommand;
use Windwalker\Core\Migration\Command\PhinxCommand;
use Windwalker\Core\Package\AbstractPackage;
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
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;

/**
 * The Console class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class WindwalkerConsole extends Console implements WindwalkerApplicationInterface, DispatcherAwareInterface
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
	 * Class init.
	 *
	 * @param   Container    $container  The DI Container object.
	 * @param   IOInterface  $io         The Input and output handler.
	 * @param   Registry     $config     Application's config object.
	 */
	public function __construct(Container $container = null, IOInterface $io = null, Registry $config = null)
	{
		$this->container = $container instanceof Container ? $container : Ioc::factory();

		parent::__construct($io, $config);
	}

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

		// Register system providers
		$this->container->registerServiceProvider(new SystemProvider($this))
			->registerServiceProvider(new ConsoleProvider($this));

		// Register custom providers
		$this->registerProviders($this->container);

		// Register commands
		$this->registerCommands();

		// Load packages
		PackageHelper::registerPackages($this->loadPackages(), $this->container);
	}

	/**
	 * registerCommands
	 *
	 * @return  void
	 */
	public function registerCommands()
	{
		$this->addCommand(new PhinxCommand);
		$this->addCommand(new MigrationCommand);
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
		$providers = $this->loadProviders();

		foreach ($providers as $provider)
		{
			$container->registerServiceProvider($provider);
		}
	}

	/**
	 * loadProviders
	 *
	 * @return  ServiceProviderInterface[]
	 */
	public function loadProviders()
	{
		return array(
			'event'    => new EventProvider,
			'database' => new DatabaseProvider,
			'cache'    => new CacheProvider,
			'lang'     => new LanguageProvider,
		);
	}

	/**
	 * getPackages
	 *
	 * @return  array
	 */
	public function loadPackages()
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
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function triggerEvent($event, $args = array())
	{
		/** @var \Windwalker\Event\Dispatcher $dispatcher */
		$dispatcher = $this->container->get('system.dispatcher');

		$dispatcher->triggerEvent($event);

		return $this;
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage($name)
	{
		$key = 'package.' . strtolower($name);

		if ($this->container->exists($key))
		{
			return $this->container->get($key);
		}

		return null;
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setContainer($container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		return $this->container;
	}
}
 