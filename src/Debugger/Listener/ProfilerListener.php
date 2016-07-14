<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\Listener;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Core\DateTime\DateTime;
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Ioc;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;
use Windwalker\Data\DataSet;
use Windwalker\Debugger\Helper\ComposerInformation;
use Windwalker\DI\Container;
use Windwalker\Event\Event;
use Windwalker\Profiler\Point\Collector;
use Windwalker\Profiler\Profiler;
use Windwalker\Structure\StructureHelper;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ProfilerListender class.
 * 
 * @since  2.1.1
 */
class ProfilerListener
{
	/**
	 * onAfterInitialise
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterInitialise(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');
		$input     = $container->get('input');

		// Push exception collector
		$container->get('error.handler')->addHandler(function($exception) use ($container)
		{
			if (!$container->exists('debugger.collector'))
			{
				return;
			}

			$collector = $container->get('debugger.collector');

			/** @var \Exception $exception */
			$collector['exception'] = array(
				'type'    => get_class($exception),
				'message' => $exception->getMessage(),
				'code'    => $exception->getCode(),
				'file'    => $exception->getFile(),
				'line'    => $exception->getLine(),
				'trace'   => BacktraceHelper::normalizeBacktraces($exception->getTrace())
			);
		});

		$collector['system.name'] = $event['app']->getName();
		$collector['system.time'] = DateTime::create('now', DateTime::TZ_LOCALE)->format(DateTime::FORMAT_YMD_HIS);
		$collector['system.uri']  = get_object_vars($container->get('uri'));
		$collector['system.ip']   = $input->server->getString('REMOTE_ADDR');
		$collector['system.method.http']   = $input->getMethod();
		$collector['system.method.custom'] = strtoupper($input->get('_method'));

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'system.process'
		));
	}

	/**
	 * onAfterRouting
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterRegisterMiddlewares(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$profiler  = $container->get('profiler');

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'system.process'
		));
	}

	/**
	 * onAfterRouting
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterRouting(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		/** @var CoreRouter $router */
		$router = $event['app']->router;

		$collector['package.name']    = $container->get('current.package')->getName();
		$collector['package.class']   = get_class($container->get('current.package'));
		$collector['controller.task'] = $container->get('current.package')->getTask();
		$collector['routing.matcher'] = get_class($router->getMatcher());
		$collector['routing.matched'] = iterator_to_array($container->get('current.route'));
		$collector['routing.routes']  = StructureHelper::dumpObjectValues($router->getRoutes());

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'system.process'
		));
	}

	/**
	 * onControllerBeforeExecute
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onControllerBeforeExecute(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Profiler  $profiler
		 */
		$container = $event['controller']->getContainer();
		$profiler  = $container->get('profiler');

		$name = $event['controller']->getPackage()->name . '@' . $event['controller']->getName() . '::' . ReflectionHelper::getShortName($event['controller']);

		$profiler->mark(__FUNCTION__ . ' / ' . $name . ' (' . uniqid() . ')', array(
			'tag' => 'package.process'
		));
	}

	/**
	 * onControllerAfterExecute
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onControllerAfterExecute(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Profiler  $profiler
		 */
		$container = $event['controller']->getContainer();
		$profiler  = $container->get('profiler');

		$name = $event['controller']->getPackage()->name . '@' . $event['controller']->getName() . '::' . ReflectionHelper::getShortName($event['controller']);

		$profiler->mark(__FUNCTION__ . ' / ' . $name . ' (' . uniqid() . ')', array(
			'tag' => 'package.process'
		));
	}

	/**
	 * onViewBeforeRender
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onViewBeforeRender(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Profiler  $profiler
		 */
		$container = $event['view']->getPackage()->getContainer();
		$profiler  = $container->get('profiler');

		$name = $event['view']->getPackage()->name . '@' . $event['view']->getName();

		$profiler->mark(__FUNCTION__ . ' / ' . $name . ' (' . uniqid() . ')', array(
			'tag' => 'package.process'
		));
	}

	/**
	 * onViewAfterRender
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onViewAfterRender(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Profiler  $profiler
		 */
		$container = $event['view']->getPackage()->getContainer();
		$profiler  = $container->get('profiler');

		$name = $event['view']->getPackage()->name . '@' . $event['view']->getName();

		$profiler->mark(__FUNCTION__ . ' / ' . $name . ' (' . uniqid() . ')', array(
			'tag' => 'package.process'
		));
	}

	/**
	 * onPackageAfterExecute
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onPackageAfterExecute(Event $event)
	{
		/**
		 * @var AbstractPackage    $package
		 * @var AbstractController $controller
		 */
		$package = $event['package'];
		$controller = $event['controller'];

		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $package->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		$collector['controller.main'] = get_class($controller);

		$collector->push('controller.executed', array(
			'controller' => get_class($controller),
			'task'       => $event['task'],
			'input'      => $controller->getInput()->toArray(),
			'variables'  => $event['variables'],
		));

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'package.process'
		));
	}

	/**
	 * onAfterRender
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterExecute(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'system.process'
		));
	}

	/**
	 * onBeforeRedirect
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onBeforeRedirect(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		$collector['redirect'] = array(
			'url'   => $event['url'],
			'moved' => $event['moved']
		);
	}

	/**
	 * onAfterRender
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterRespond(Event $event)
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = $event['app']->getContainer();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		$profiler->mark(__FUNCTION__, array(
			'tag' => 'system.process'
		));
	}

	/**
	 * collectAllInformation
	 *
	 * @return  void
	 */
	public static function collectAllInformation()
	{
		/**
		 * @var Container $container
		 * @var Collector $collector
		 * @var Profiler  $profiler
		 */
		$container = Ioc::factory();
		$collector = $container->get('debugger.collector');
		$profiler  = $container->get('profiler');

		// Packages
		$packages = PackageHelper::getPackages();
		$pkgs = array();

		foreach ($packages as $package)
		{
			$pkgs[$package->getName()] = $package->getDir();
		}

		// Windwalker Information
		$collector['windwalker.version.framework'] = ComposerInformation::getInstalledVersion('windwalker/framework');
		$collector['windwalker.version.core'] = ComposerInformation::getInstalledVersion('windwalker/core');
		$collector['windwalker.config'] = Ioc::getConfig()->toArray();

		// PHP
		$collector['system.php.version']    = PHP_VERSION;
		$collector['system.php.extensions'] = get_loaded_extensions();

		// Load all inputs
		$collector['request'] = Ioc::getInput()->dumpAllInputs();

		// SQL Explain
		if (Ioc::exists('database'))
		{
			$queries = $collector['database.queries'];
			$db = Ioc::getDatabase();

			foreach ($queries as $k => $data)
			{
				if (stripos(trim($data['query']), 'SELECT') !== 0)
				{
					continue;
				}

				$query = $db->getQuery(true);
				$query->setQuery('EXPLAIN ' . $data['query']);

				if (!empty($data['bounded']))
				{
					foreach ((array) $data['bounded'] as $key => $bind)
					{
						$bind = (object) $bind;
						$query->bind($key, $bind->value, $bind->dataType, $bind->length, $bind->driverOptions);
					}
				}

				$collector['database.queries.' . $k . '.explain'] = $db->setQuery($query)->loadAll();
			}

			// Database Information
			$collector['database.driver.name'] = $db->getName();
			$collector['database.driver.class'] = get_class($db);
			$collector['database.info'] = $db->getOptions();

			if ($queries instanceof DataSet)
			{
				$collector['database.queries'] = iterator_to_array($queries);
			}
		}

		// Events
		/** @var EventDispatcher $dispatcher */
		$dispatcher = $container->get('dispatcher');

		$collector['event.executed'] = $dispatcher->getCollector();

		$listenersMapping = array();

		foreach ($dispatcher->getListeners() as $eventName => $listeners)
		{
			$listenersMapping[$eventName] = array();

			foreach ($listeners as $listener)
			{
				if ($listener instanceof \Closure)
				{
					continue;
				}

				if (is_object($listener))
				{
					$listener = array(get_class($listener), $eventName);
				}

				$listenersMapping[$eventName][] = $listener;
			}
		}

		$collector['event.listeners'] = $listenersMapping;

		// Headers
		$collector['system.http.status'] = $collector['exception'] ? $collector['exception']['code'] : http_response_code();
		$collector['system.http.headers'] = headers_list();
	}
}
