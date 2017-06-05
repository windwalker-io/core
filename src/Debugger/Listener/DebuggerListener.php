<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\Listener;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Ioc;
use Windwalker\Core\Repository\ModelRepository;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Widget\Widget;
use Windwalker\Data\Data;
use Windwalker\Debugger\DebuggerPackage;
use Windwalker\Debugger\Helper\PageRecordHelper;
use Windwalker\Debugger\Helper\TimelineHelper;
use Windwalker\Debugger\Model\DashboardModel;
use Windwalker\Event\Event;
use Windwalker\Filesystem\File;
use Windwalker\IO\Input;
use Windwalker\Profiler\Profiler;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Arr;

/**
 * The DebuggerListener class.
 * 
 * @since  2.1.1
 */
class DebuggerListener
{
	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * Property package.
	 *
	 * @var  DebuggerPackage
	 */
	protected $package;

	/**
	 * Class init.
	 *
	 * @param  DebuggerPackage $package
	 */
	public function __construct(DebuggerPackage $package)
	{
		$this->package = $package;
		$this->app = $package->app;
	}

	/**
	 * onAfterInitialise
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onAfterInitialise(Event $event)
	{
		$this->app = $event['app'];
	}

	/**
	 * onPackageBeforeExecute
	 *
	 * @param Event $event
	 *
	 * @return  void
	 */
	public function onPackageBeforeExecute(Event $event)
	{
		if ($event['hmvc'])
		{
			return;
		}

		/** @var Input $input */
		$package = $event['package'];

		if (!$package instanceof DebuggerPackage)
		{
			return;
		}

		$controller = $event['controller'];
		$input = $controller->getInput();
		$app   = Ioc::getApplication();
		$uri   = Ioc::getUriData();

		$noRedirect = [
			'asset',
			'mail'
		];
		
		if (in_array($app->get('route.short_name'), $noRedirect))
		{
			return;
		}

		/** @var ModelRepository $model */
		$model = $controller->getModel('Dashboard');

		if ($input->get('refresh'))
		{
			$hash = $input->getString('hash');
			$hash = $hash ? '#' . $hash : '';

			$input->set('id', null);

			if (Ioc::exists('session'))
			{
				$session = Ioc::getSession();
				$session->set('debugger.current.id', null);
			}

			$item = $model->getLastItem();

			if ($item)
			{
				$app->redirect($package->router->route($app->get('route.matched'), ['id' => $item['id']]) . $hash);

				return;
			}
		}

		if (!$id = $input->get('id'))
		{
			$session = Ioc::getSession();

			// Get id from session
			$id = $session->get('debugger.current.id');

			// If session not exists, get last item.
			if (!$id)
			{
				$item = $model->getLastItem();

				// No item, redirect to front-end.
				if (!$item)
				{
					$app->redirect($uri['base.full'] . $uri['script']);

					return;
				}

				$id = $item['id'];
			}

			// set id to session and redirect
			$session->set('debugger.current.id', $id);

			$app->redirect($package->router->route($app->get('route.matched'), ['id' => $id]));

			return;
		}

		$itemModel = $controller->getModel('Profiler');

		if (!$itemModel->hasItem($id))
		{
			$session = Ioc::getSession();
			$session->set('debugger.current.id', null);

			$app->redirect($package->router->route('dashboard'));
		}

		if (Ioc::exists('session'))
		{
			$session = Ioc::getSession();
			$session->set('debugger.current.id', $id);
		}
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
		if (!PackageHelper::getPackage() instanceof DebuggerPackage)
		{
			return;
		}

//		$theme = $this->package->getDir() . '/Resources/media/css/theme.min.css';
//
//		if (!is_file($theme))
//		{
//			$theme = $this->package->getDir() . '/Resources/media/css/theme.css';
//		}
//
//		$main = $this->package->getDir() . '/Resources/media/css/debugger.css';
//
//		$event['data']->themeStyle = file_get_contents($theme) . "\n\n" . file_get_contents($main);
	}

	/**
	 * onAfterRender
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		if (!$this->app instanceof WebApplication)
		{
			return;
		}

		if ($this->app->getPackage() instanceof DebuggerPackage)
		{
			return;
		}

		ProfilerListener::collectAllInformation();

		$container = $this->app->getContainer();

		$profiler = $container->get('profiler');
		$collector = $container->get('debugger.collector');

		$id = uniqid();
		$collector['id'] = $id;

		$this->pushDebugConsole($collector, $profiler);

		$this->deleteOldFiles();

		$data = [
			'profiler'  => $profiler,
			'collector' => $collector
		];

		$data = serialize($data);

		$dir = WINDWALKER_CACHE . '/profiler/' . PageRecordHelper::getFolderName($id);

		File::write($dir . '/' . $id, $data);
	}

	/**
	 * pushDebugConsole
	 *
	 * @param Structure $collector
	 * @param Profiler  $profiler
	 */
	protected function pushDebugConsole(Structure $collector, Profiler $profiler)
	{
		if (!$this->package->config->get('console.enabled', 1))
		{
			return;
		}

		// Prepare CSS
		$style = $this->package->getDir() . '/Resources/asset/css/console/style.css';

		// Prepare Time
		$points = $profiler->getPoints();
		$first = array_shift($points);
		$last = array_pop($points);
		$time = $profiler->getTimeBetween($first->getName(), $last->getName());
		$memory = $profiler->getMemoryBetween($first->getName(), $last->getName());

		$data = new Data;
		$data->collector = $collector;
		$data->style = file_get_contents($style);

		// Execute Time
		$data->time = $time * 1000;
		$data->memory = $memory / 1048576;

		// Queries
		$data->queryTimes = $collector['database.query.times'];
		$data->queryTotalTime = $collector['database.query.total.time'] * 1000;
		$data->queryTotalMemory = $collector['database.query.total.memory'] / 1048576;

		// Messages
		$data->messages = (array) $collector['request.session._flash'];
		$data->debugMessages = (array) $collector['debug.messages'];
		$data->messagesCount = [
			'messages' => count(Arr::flatten($data->messages)),
			'debug' => count($data->debugMessages)
		];

		$data->timeStyle = TimelineHelper::getStateColor($data->time, 250);
		$data->memoryStyle = TimelineHelper::getStateColor($data->memory, 5);

		$widget = new Widget('debugger.console', 'php', $this->package->getName());

		echo $widget->render($data);
	}

	/**
	 * deleteOldFiles
	 *
	 * @return  void
	 */
	protected function deleteOldFiles()
	{
		$model = new DashboardModel;

		$files = $model->getFiles();
		$items = [];

		/** @var \SplFileInfo $file */
		foreach ($files as $file)
		{
			$items[$file->getMTime()] = $file;
		}

		krsort($items);

		$i = 0;

		/** @var \SplFileInfo $file */
		foreach ($items as $file)
		{
			$i++;

			if ($i < 100)
			{
				continue;
			}

			File::delete($file->getPathname());
		}
	}
}
