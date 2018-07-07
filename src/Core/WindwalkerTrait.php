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
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\ListenerPriority;
use Windwalker\Structure\Structure;

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
     * @return  Structure
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * boot
     *
     * @return  void
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        // Version check
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            exit('Please use PHP 5.6 or later.');
        }

        $this->bootTraits($this);

        $this->mode = $this->loadMode();

        $this->loadConfiguration($this->config);

        $this->registerProviders();

        // Set some default objects
        if ($this->container->exists('dispatcher')) {
            $this->dispatcher = $this->container->get('dispatcher');
        } else {
            $this->dispatcher = new NullObject();
        }

        $this->logger = $this->container->get('logger');

        $this->registerListeners();

        $this->registerPackages();

        $this->triggerEvent('onAfterInitialise', ['app' => $this]);

        $this->booted = true;
    }

    /**
     * loadConfiguration
     *
     * @param Structure $config
     * @param string    $name
     *
     * @return  void
     */
    protected function loadConfiguration(Structure $config, $name = null)
    {
        $name = $name ?: $this->getName();

        // Load library config
        $configName = $this->isWeb() ? 'web' : 'console';

        $config->loadFile(__DIR__ . '/../../config/' . $configName . '.php', 'php', ['load_raw' => true]);

        // Load application config
        $file = $this->rootPath . '/etc/app/' . $name . '.php';

        if (is_file($file)) {
            $config->loadFile($file, 'php', ['load_raw' => true]);
        }

        $configs = (array) $config->get('configs', []);

        ksort($configs);

        foreach ($configs as $file) {
            if ($file === false || !is_file($file)) {
                continue;
            }

            $config->loadFile($file, pathinfo($file, PATHINFO_EXTENSION), ['load_raw' => true]);
        }
        // TODO: Variables override
    }

    /**
     * registerProviders
     *
     * @return  void
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function registerProviders()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        // Register Aliases
        $aliases = (array) $this->get('di.aliases');

        foreach ($aliases as $alias => $target) {
            $container->alias($alias, $target);
        }

        $container->registerServiceProvider(new SystemProvider($this, $this->config));

        $providers = (array) $this->config->get('providers');

        foreach ($providers as $interface => &$provider) {
            if (is_subclass_of($provider, ServiceProviderInterface::class)) {
                // Handle provider
                if (is_string($provider) && class_exists($provider)) {
                    $provider = $container->newInstance($provider);
                }

                if ($provider === false) {
                    continue;
                }

                $container->registerServiceProvider($provider);

                if (method_exists($provider, 'boot')) {
                    $provider->boot($container);
                }
            } else {
                // Handle Service
                if (is_numeric($interface)) {
                    $container->prepareSharedObject($provider);
                } else {
                    $container->bindShared($interface, $provider);
                }
            }
        }

        foreach ($providers as $provider) {
            if (is_subclass_of($provider, ServiceProviderInterface::class)
                && method_exists($provider, 'bootDeferred')) {
                $provider->bootDeferred($container);
            }
        }
    }

    /**
     * registerPackages
     *
     * @return  static
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
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
        $listeners  = (array) $this->get('listeners');
        $dispatcher = $this->getDispatcher();

        $defaultOptions = [
            'class' => '',
            'priority' => ListenerPriority::NORMAL,
            'enabled' => true,
        ];

        foreach ($listeners as $name => $listener) {
            if (is_string($listener) || is_callable($listener)) {
                $listener = ['class' => $listener];
            }

            $listener = array_merge($defaultOptions, (array) $listener);

            if (!$listener['enabled']) {
                continue;
            }

            if (is_callable($listener['class']) && !is_numeric($name)) {
                $dispatcher->listen($name, $listener['class']);
            } else {
                $dispatcher->addListener($this->container->newInstance($listener['class']), $listener['priority']);
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
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

    /**
     * loadMode
     *
     * @return  string
     */
    protected function loadMode()
    {
        if (isset($_ENV['WINDWLAKER_MODE'])) {
            return $_ENV['WINDWLAKER_MODE'];
        }

        $file = $this->rootPath . '/.mode';

        if (!is_file($file)) {
            return 'dev';
        }

        $mode = trim(file_get_contents($file));
        $mode = $mode ?: 'dev';

        return $mode;
    }

    /**
     * isOffline
     *
     * @return  bool
     */
    public function isOffline()
    {
        $file = $this->get('path.temp', $this->rootPath . '/tmp') . '/offline';

        return is_file($file);
    }
}
