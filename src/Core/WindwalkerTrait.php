<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core;

use Symfony\Component\Yaml\Yaml;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Object\NullObject;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Provider\SystemProvider;
use Windwalker\DI\Container;
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

		$this->registerProviders();

		// Set some default objects
		if ($this->container->exists('system.dispatcher'))
		{
			$this->dispatcher = $this->container->get('dispatcher');
		}
		else
		{
			$this->dispatcher = new NullObject;
		}

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

		$file = $this->configPath . '/' . $name . '.php';

		if (is_file($file))
		{
			$config->loadFile($file, 'php');
		}

		$configs = (array) $config->get('configs', []);

		krsort($configs);

		foreach ($configs as $file)
		{
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

		$container->registerServiceProvider(new SystemProvider($this));

		$providers = (array) $this->config->get('providers');

		foreach ($providers as $provider)
		{
			if (is_string($provider) && class_exists($provider))
			{
				$provider = new $provider($this);
			}

			if (is_callable([$provider, 'boot']))
			{
				$provider->boot($container);
			}

			$container->registerServiceProvider($provider);
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
		$listeners = (array) $this->config->get('listeners');

		foreach ($listeners as $listener)
		{
			if (is_string($listener) && class_exists($listener))
			{
				$listener = new $listener($this);
			}

			$this->dispatcher->addListener($listener);
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
	 * Load routing profiles as an array.
	 *
	 * @return  array
	 */
	public static function loadRouting()
	{
		return Yaml::parse(file_get_contents(WINDWALKER_ETC . '/routing.yml'));
	}

	/**
	 * Prepare system path.
	 *
	 * Write your custom path to $config['path.xxx'].
	 *
	 * @param   Registry  $config  The config registry object.
	 *
	 * @return  void
	 */
	public static function prepareSystemPath(Registry $config)
	{
		$config['path.root']       = WINDWALKER_ROOT;
		$config['path.bin']        = WINDWALKER_BIN;
		$config['path.cache']      = WINDWALKER_CACHE;
		$config['path.etc']        = WINDWALKER_ETC;
		$config['path.logs']       = WINDWALKER_LOGS;
		$config['path.resources']  = WINDWALKER_RESOURCES;
		$config['path.source']     = WINDWALKER_SOURCE;
		$config['path.temp']       = WINDWALKER_TEMP;
		$config['path.templates']  = WINDWALKER_TEMPLATES;
		$config['path.vendor']     = WINDWALKER_VENDOR;
		$config['path.public']     = WINDWALKER_PUBLIC;
		$config['path.migrations'] = WINDWALKER_MIGRATIONS;
		$config['path.seeders']    = WINDWALKER_SEEDERS;
		$config['path.languages']  = WINDWALKER_LANGUAGES;
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
