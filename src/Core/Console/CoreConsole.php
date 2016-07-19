<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Console;
use Windwalker\Console\IO\IOFactory;
use Windwalker\Console\IO\IOInterface;
use Windwalker\Console\IO\NullInput;
use Windwalker\Core;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Debugger\Helper\ComposerInformation;
use Windwalker\DI\Container;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Language\Language;
use Windwalker\Structure\Structure;
use Windwalker\Session\Session;

/**
 * The Console class.
 *
 * @property-read  Container                     container
 * @property-read  Core\Logger\LoggerManager     logger
 * @property-read  Structure                     config
 * @property-read  Core\Event\EventDispatcher    dispatcher
 * @property-read  AbstractDatabaseDriver        database
 * @property-read  Core\Router\MainRouter        router
 * @property-read  Language                      language
 * @property-read  Core\Renderer\RendererManager renderer
 * @property-read  Core\Cache\CacheFactory       cache
 * @property-read  Session                       session
 * 
 * @since  2.0
 */
class CoreConsole extends Console implements Core\Application\WindwalkerApplicationInterface, DispatcherAwareInterface
{
	use BootableTrait;
	use Core\WindwalkerTrait;

	/**
	 * The Console name.
	 *
	 * @var  string
	 *
	 * @since  2.0
	 */
	protected $name = 'console';

	/**
	 * The Console title.
	 *
	 * @var  string
	 */
	protected $title = 'Windwalker Console';

	/**
	 * Version of this application.
	 *
	 * @var string
	 */
	protected $version = '3';

	/**
	 * The DI container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Property config.
	 *
	 * @var Structure
	 */
	protected $config;

	/**
	 * Property mode.
	 *
	 * @var  string
	 */
	protected $mode;

	/**
	 * Class init.
	 *
	 * @param   IOInterface $io     The Input and output handler.
	 * @param   Config      $config Application's config object.
	 */
	public function __construct(IOInterface $io = null, Config $config = null)
	{
		$this->config = $config instanceof Config ? $config : new Config;
		$this->name   = $this->config->get('name', $this->name);

		Core\Ioc::setProfile($this->name);

		$this->container = Core\Ioc::factory();

		parent::__construct($io, $this->config);
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function init()
	{
		if (class_exists(ComposerInformation::class))
		{
			$this->version = ComposerInformation::getInstalledVersion('windwalker/core');
		}
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
	 * registerCommands
	 *
	 * @return  void
	 */
	public function registerCommands()
	{
		$commands = (array) $this->get('console.commends');

		foreach ($commands as $command)
		{
			$this->addCommand($command);
		}
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
		$this->boot();

		$this->registerRootCommand();

		$this->registerCommands();

		$this->prepareExecute();

		$this->triggerEvent('onBeforeExecute', ['app' => $this]);

		// Perform application routines.
		$exitCode = $this->doExecute();

		$this->triggerEvent('onAfterExecute', ['app' => $this]);

		return $this->postExecute($exitCode);
	}

	/**
	 * Register default command.
	 *
	 * @return  static  Return this object to support chaining.
	 *
	 * @since  2.0
	 */
	public function registerRootCommand()
	{
		parent::registerRootCommand();

		$this->getRootCommand()
			->addGlobalOption('n')
			->alias('no-interactive')
			->defaultValue(false)
			->description('Ignore interactions and assume to default value.');

		return $this;
	}

	/**
	 * Prepare execute hook.
	 *
	 * @return  void
	 */
	protected function prepareExecute()
	{
		if ($this->getRootCommand()->getOption('n'))
		{
			IOFactory::getIO()->setInput(new NullInput);
		}
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
		$dispatcher = $this->container->get('dispatcher');

		$dispatcher->triggerEvent($event, $args);

		return $event;
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage($name = null)
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
		return $this->container->get('dispatcher');
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

	/**
	 * addMessage
	 *
	 * @param string|array $messages
	 * @param string       $type
	 *
	 * @return  static
	 */
	public function addMessage($messages, $type = null)
	{
		switch ($type)
		{
			case 'success':
			case 'green':
				$tag = '<info>%s</info>';
				break;

			case 'warning':
			case 'yellow':
				$tag = '<comment>%s</comment>';
				break;

			case 'info':
			case 'blue':
				$tag = '<option>%s</option>';
				break;

			case 'error':
			case 'danger':
			case 'red':
				$tag = '<error>%s</error>';
				break;

			default:
				$tag = '%s';
				break;
		}

		foreach ((array) $messages as $message)
		{
			$time = gmdate('Y-m-d H:i:s');

			$this->out(sprintf('[%s] ' . $tag, $time, $message));
		}

		return $this;
	}

	/**
	 * Method to get property Mode
	 *
	 * @return  string
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * is utilized for reading data from inaccessible members.
	 *
	 * @param   $name  string
	 *
	 * @return  mixed
	 * @throws \OutOfRangeException
	 */
	public function __get($name)
	{
		$diMapping = [
			'io'         => 'system.io',
			'dispatcher' => 'system.dispatcher',
			'database'   => 'system.database',
			'language'   => 'system.language',
			'cache'      => 'system.cache',
		];

		if (isset($diMapping[$name]))
		{
			return $this->container->get($diMapping[$name]);
		}

		$allowNames = array(
			'container',
			'config'
		);

		if (in_array($name, $allowNames))
		{
			return $this->$name;
		}

		throw new \OutOfRangeException(sprintf('property "%s" not found in %s', $name, get_called_class()));
	}
}
