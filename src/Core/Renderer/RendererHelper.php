<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Ioc;
use Windwalker\Core\Utilities\Iterator\PriorityQueue;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Renderer\TwigRenderer;
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
	 * @var  PriorityQueue
	 */
	protected static $paths;

	/**
	 * getRenderer
	 *
	 * @param string $type
	 *
	 * @return  RendererInterface
	 */
	public static function getRenderer($type = 'php')
	{
		$class = sprintf('Windwalker\Renderer\%sRenderer', ucfirst($type));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('%s renderer not supported.', $type));
		}

		return new $class(static::getGlobalPaths());
	}

	/**
	 * getPhpRenderer
	 *
	 * @return  PhpRenderer
	 */
	public static function getPhpRenderer()
	{
		return static::getRenderer('php');
	}

	/**
	 * getBladeRenderer
	 *
	 * @return  BladeRenderer
	 */
	public static function getBladeRenderer()
	{
		return static::getRenderer('blade');
	}

	/**
	 * getTwigRenderer
	 *
	 * @return  TwigRenderer
	 */
	public static function getTwigRenderer()
	{
		return static::getRenderer('twig');
	}

	/**
	 * getGlobalPaths
	 *
	 * @return  PriorityQueue
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
	 * @return  PriorityQueue
	 */
	protected static function getPaths()
	{
		if (!static::$paths)
		{
			static::$paths = new PriorityQueue;

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
		$config = Ioc::getConfig();

		// Priority (1)
		static::$paths->insert(
			realpath($config->get('path.templates')),
			Priority::LOW
		);

		// Priority (2)
		static::$paths->insert(
			realpath(__DIR__ . '/../Resources/Templates'),
			Priority::LOW
		);

		// Priority (3)
//		static::$paths->insert(
//			realpath($config->get('path.templates') . '/_global'),
//			Priority::LOW - 20
//		);
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
