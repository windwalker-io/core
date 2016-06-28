<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Object\NullObject;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Provider\SystemProvider;
use Windwalker\DI\Container;
use Windwalker\Event\ListenerPriority;
use Windwalker\Registry\Registry;

/**
 * The main Windwalker instantiate class.
 *
 * This class will load in both Web and Console. Write some configuration if you want to use in all environment.
 *
 * @since  2.0
 */
trait WindwalkerTrait
{
	/**
	 * Property booted.
	 *
	 * @var  boolean
	 */
	protected $booted = false;

	/**
	 * getName
	 *
	 * @return  string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * bootWindwalkerTrait
	 *
	 * @param WindwalkerApplicationInterface $app
	 *
	 * @return  void
	 */
	protected function bootWindwalkerTrait(WindwalkerApplicationInterface $app)
	{
//		$this->config = new ConfigRegistry($this->config->toArray());
	}

	/**
	 * getConfig
	 *
	 * @return  Registry
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * boot
	 *
	 * @return  void
	 */
	public function boot()
	{
		if ($this->booted)
		{
			return;
		}

		// Version check
		if (version_compare(PHP_VERSION, '5.6', '<'))
		{
			exit('Please use PHP 5.6 or later.');
		}

		$this->bootTraits($this);

		$this->loadConfiguration($this->config);

		$this->mode = $this->config->get('system.mode', 'prod');

		$this->registerProviders();

		// Set some default objects
		if ($this->container->exists('dispatcher'))
		{
			$this->dispatcher = $this->container->get('dispatcher');
		}
		else
		{
			$this->dispatcher = new NullObject;
		}

		$this->logger = $this->container->get('logger');

		$this->registerListeners();

		$this->registerPackages();

		$this->triggerEvent('onAfterInitialise', array('app' => $this));

		$this->booted = true;
	}

	/**
	 * loadConfiguration
	 *
	 * @param Registry $config
	 * @param string   $name
	 *
	 * @return  void
	 */
	protected function loadConfiguration(Registry $config, $name = null)
	{
		$name = $name ? : $this->getName();

		// Load library config
		$configName = $this->isWeb() ? 'web' : 'console';

		$config->loadFile(__DIR__ . '/../../config/' . $configName . '.php', 'php');

		// Load application config
		$file = $this->configPath . '/' . $name . '.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php');
		}

		$configs = (array) $config->get('configs', []);

		ksort($configs);
		
		foreach ($configs as $file)
		{
			if (!is_file($file))
			{
				throw new \RuntimeException(sprintf('Config file: %s not exists', $file));
			}

			$config->loadFile($file, pathinfo($file, PATHINFO_EXTENSION));
		}

		// TODO: Variables override
	}

	/**
	 * registerProviders
	 *
	 * @return  void
	 */
	protected function registerProviders()
	{
		/** @var Container $container */
		$container = $this->getContainer();

		// Register Aliases
		$aliases = (array) $this->get('di.aliases');

		foreach ($aliases as $alias => $target)
		{
			$container->alias($alias, $target);
		}

		$container->registerServiceProvider(new SystemProvider($this));

		$providers = (array) $this->config->get('providers');

		foreach ($providers as $provider)
		{
			if (is_string($provider) && class_exists($provider))
			{
				$provider = new $provider($this);
			}
			
			if (!$provider)
			{
				continue;
			}

			$container->registerServiceProvider($provider);

			if (is_callable([$provider, 'boot']))
			{
				$provider->boot($container);
			}
		}
	}

	/**
	 * registerPackages
	 *
	 * @return  static
	 */
	protected function registerPackages()
	{
		$packages = (array) $this->config->get('packages');

		/** @var PackageResolver $resolver */
		$resolver = $this->container->get('package.resolver');

		$resolver->registerPackages($packages);

		return $this;
	}

	/**
	 * registerListeners
	 *
	 * @return  void
	 */
	protected function registerListeners()
	{
		$listeners = (array) $this->get('listeners');
		$dispatcher = $this->getDispatcher();

		$defaultOptions = array(
			'class'    => '',
			'priority' => ListenerPriority::NORMAL,
			'enabled'  => true
		);

		foreach ($listeners as $name => $listener)
		{
			if (is_string($listener) || is_callable($listener))
			{
				$listener = array('class' => $listener);
			}

			$listener = array_merge($defaultOptions, (array) $listener);

			if (!$listener['enabled'])
			{
				continue;
			}

			if (is_callable($listener['class']) && !is_numeric($name))
			{
				$dispatcher->listen($name, $listener['class']);
			}
			else
			{
				$dispatcher->addListener(new $listener['class']($this), $listener['priority']);
			}
		}
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
		/** @var PackageResolver $resolver */
		$resolver = $this->container->get('package.resolver');

		return $resolver->getPackage($name);
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
		/** @var PackageResolver $resolver */
		$resolver = $this->container->get('package.resolver');

		$resolver->addPackage($name, $package);

		return $this;
	}

	/**
	 * isConsole
	 *
	 * @return  boolean
	 */
	public function isConsole()
	{
		return $this instanceof CoreConsole;
	}

	/**
	 * isWeb
	 *
	 * @return  boolean
	 */
	public function isWeb()
	{
		return $this instanceof WebApplication;
	}
}
