<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Console;
use Windwalker\Console\IO\IOInterface;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Asset\Command\AssetCommand;
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
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;

/**
 * The Console class.
 * 
 * @since  2.0
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
	public function __construct(Container $container = null, IOInterface $io = null, $config = null)
	{
		$this->config    = $config    instanceof Registry  ? $config    : new Registry($config);
		$this->container = $container instanceof Container ? $container : new Container;

		$this->name = $this->config->get('name', $this->name);

		Ioc::setProfile($this->name);
		Ioc::setContainer($this->name, $this->container);

		parent::__construct($io, $config);
	}

	/**
	 * Method to get property Name
	 *
	 * @return  string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Method to set property name
	 *
	 * @param   string $name
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
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
		$this->container->get('package.resolver')->registerPackages($this->loadPackages(), $this->container);

		$this->triggerEvent('onAfterInitialise', array('app' => $this));
	}

	/**
	 * registerCommands
	 *
	 * @return  void
	 */
	public function registerCommands()
	{
		$this->addCommand(new AssetCommand);
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
	public static function loadProviders()
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
	public static function loadPackages()
	{
		return array();
	}

	/**
	 * Execute the application.
	 *
	 * @return  int  The Unix Console/Shell exit code.
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		$this->prepareExecute();

		$this->triggerEvent('onBeforeExecute');

		// Perform application routines.
		$exitCode = $this->doExecute();

		$this->triggerEvent('onAfterExecute');

		return $this->postExecute($exitCode);
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
	 * @since   2.0
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
	 * addPackage
	 *
	 * @param string          $name
	 * @param AbstractPackage $package
	 *
	 * @return  static
	 */
	public function addPackage($name, AbstractPackage $package)
	{
		$this->container->get('package.resolver')->addPackage($name, $package);

		return $this;
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

	/**
	 * getDispatcher
	 *
	 * @return  DispatcherInterface
	 */
	public function getDispatcher()
	{
		return $this->container->get('system.dispatcher');
	}

	/**
	 * setDispatcher
	 *
	 * @param   DispatcherInterface $dispatcher
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->container->share('system.dispatcher', $dispatcher);

		return $this;
	}
}
