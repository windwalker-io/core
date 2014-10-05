<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Ioc;
use Windwalker\Utilities\Queue\Priority;

/**
 * The RendererFactory class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class RendererHelper
{
	/**
	 * Property paths.
	 *
	 * @var  \SplPriorityQueue
	 */
	protected static $paths;

	/**
	 * getGlobalPaths
	 *
	 * @return  \SplPriorityQueue
	 */
	public static function getGlobalPaths()
	{
		return clone static::getPaths();
	}

	/**
	 * addPath
	 *
	 * @param string $path
	 * @param int    $priority
	 *
	 * @return  void
	 */
	public static function addPath($path, $priority = Priority::LOW)
	{
		static::getPaths()->insert($path, $priority);
	}

	/**
	 * getPaths
	 *
	 * @return  \SplPriorityQueue
	 */
	protected static function getPaths()
	{
		if (!static::$paths)
		{
			static::$paths = new \SplPriorityQueue;

			static::registerPaths();
		}

		return static::$paths;
	}

	/**
	 * registerPaths
	 *
	 * @return  void
	 */
	protected static function registerPaths()
	{
		$server = Ioc::getEnvironment()->server;
		$config = Ioc::getConfig();

		static::$paths->insert(
			realpath(__DIR__ . '/../Resources/Template'),
			Priority::LOW
		);

		static::$paths->insert(
			realpath($server->getRoot() . '/../' . $config->get('view.template.global') . '/_global'),
			Priority::LOW
		);
	}

	/**
	 * reset
	 *
	 * @return  void
	 */
	public function reset()
	{
		static::$paths = null;
	}
}
 