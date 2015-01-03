<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Ioc;
use Windwalker\Core\Utilities\Iterator\PriorityQueue;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\MustacheRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\Priority;

/**
 * RendererHelper.
 * 
 * @since  2.0
 */
abstract class RendererHelper
{
	/**
	 * A PriorityQueue which extends the SplPriorityQueue.
	 *
	 * @var  PriorityQueue.
	 *
	 * @since  2.0
	 */
	protected static $paths;

	/**
	 * Create a renderer object and auto inject the global paths.
	 *
	 * @param   string  $type  Render engine name, php, blade, twig or mustache.
	 *
	 * @return  PhpRenderer|BladeRenderer|TwigRenderer|MustacheRenderer
	 *
	 * @since   2.0
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
	 *
	 * @since   2.0
	 */
	public static function getPhpRenderer()
	{
		return static::getRenderer('php');
	}

	/**
	 * getBladeRenderer
	 *
	 * @return  BladeRenderer
	 *
	 * @since   2.0
	 */
	public static function getBladeRenderer()
	{
		return static::getRenderer('blade');
	}

	/**
	 * getTwigRenderer
	 *
	 * @return  TwigRenderer
	 *
	 * @since   2.0
	 */
	public static function getTwigRenderer()
	{
		return static::getRenderer('twig');
	}

	/**
	 * getMustacheRenderer
	 *
	 * @return  MustacheRenderer
	 *
	 * @since   2.0
	 */
	public static function getMustacheRenderer()
	{
		return static::getRenderer('mustache');
	}

	/**
	 * Get a clone of global paths.
	 *
	 * @return  PriorityQueue
	 *
	 * @since   2.0
	 */
	public static function getGlobalPaths()
	{
		return clone static::getPaths();
	}

	/**
	 * Add a global path for Renderer search.
	 *
	 * @param   string  $path      The path you want to set.
	 * @param   int     $priority  Priority flag to order paths.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function addGlobalPath($path, $priority = Priority::LOW)
	{
		static::getPaths()->insert($path, $priority);
	}

	/**
	 * An alias of getGlobalPath()
	 *
	 * @param   string  $path      The path you want to set.
	 * @param   int     $priority  Priority flag to order paths.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function addPath($path, $priority = Priority::LOW)
	{
		static::addGlobalPath($path, $priority);
	}

	/**
	 * Get or create paths queue.
	 *
	 * @return  PriorityQueue
	 *
	 * @since   2.0
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
	 * Register default global paths.
	 *
	 * @return  void
	 *
	 * @since   2.0
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
	}

	/**
	 * Reset all paths.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public static function reset()
	{
		static::$paths = null;
	}
}
