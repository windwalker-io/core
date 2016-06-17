<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\DI\Container;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Renderer\MustacheRenderer;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\Priority;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The RendererFactory class.
 *
 * @since  {DEPLOY_VERSION}
 */
class RendererFactory
{
	const ENGINE_PHP      = 'php';
	const ENGINE_BLADE    = 'blade';
	const ENGINE_EDGE     = 'edge';
	const ENGINE_TWIG     = 'twig';
	const ENGINE_MUSTACHE = 'mustache';

	/**
	 * A PriorityQueue which extends the SplPriorityQueue.
	 *
	 * @var  PriorityQueue.
	 *
	 * @since  2.0
	 */
	protected $paths;

	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * RendererFactory constructor.
	 *
	 * @param PriorityQueue|array $paths
	 * @param Container           $container
	 */
	public function __construct(Container $container, $paths = array())
	{
		$this->container = $container;

		if ($paths)
		{
			$this->setPaths($paths);
		}
	}

	/**
	 * Create a renderer object and auto inject the global paths.
	 *
	 * @param   string  $type    Renderer engine name, php, blade, twig or mustache.
	 * @param   array   $config  Renderer config array.
	 *
	 * @return  AbstractRenderer|CoreRendererInterface
	 * @since   2.0
	 */
	public function getRenderer($type = self::ENGINE_PHP, $config = array())
	{
		$type = strtolower($type);

		$class = sprintf('Windwalker\Core\Renderer\%sRenderer', ucfirst($type));

		if (!class_exists($class))
		{
			$class = sprintf('Windwalker\Renderer\%sRenderer', ucfirst($type));
		}

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('%s renderer not supported.', $type));
		}

		if ($type == 'blade')
		{
			if (empty($config['cache_path']))
			{
				$config = $this->container->get('system.config');

				$config['cache_path'] = $config->get('path.cache') . '/renderer';
			}
		}

		if ($type == 'edge')
		{
			if (empty($config['cache_path']) && !isset($config['cache']))
			{
				$config = $this->container->get('system.config');

				$config['cache_path'] = $config->get('path.cache') . '/renderer';
			}
		}

		if ($type == 'twig')
		{
			if (empty($config['path_separator']))
			{
				$config['path_separator'] = '.';
			}
		}

		/** @var AbstractRenderer|CoreRendererInterface $renderer */
		$renderer = new $class(static::getGlobalPaths(), $config);

		$renderer->setPackageFinder($this->container->get('package.finder'));

		return $renderer;
	}

	/**
	 * Create php renderer.
	 *
	 * @param   array  $config  Renderer config array.
	 *
	 * @return  PhpRenderer
	 *
	 * @since   2.0
	 */
	public function getPhpRenderer($config = array())
	{
		return $this->getRenderer(static::ENGINE_PHP, $config);
	}

	/**
	 * Create blade renderer.
	 *
	 * @param   array  $config  Renderer config array.
	 *
	 * @return  BladeRenderer
	 *
	 * @since   2.0
	 */
	public function getBladeRenderer($config = array())
	{
		return $this->getRenderer(static::ENGINE_BLADE, $config);
	}

	/**
	 * getEdgeRenderer
	 *
	 * @param array $config
	 *
	 * @return  EdgeRenderer
	 */
	public function getEdgeRenderer($config = array())
	{
		return $this->getRenderer(static::ENGINE_EDGE, $config);
	}

	/**
	 * Create twig renderer.
	 *
	 * @param   array  $config  Renderer config array.
	 *
	 * @return  TwigRenderer
	 *
	 * @since   2.0
	 */
	public function getTwigRenderer($config = array())
	{
		return $this->getRenderer(static::ENGINE_TWIG, $config);
	}

	/**
	 * Create mustache renderer.
	 *
	 * @param   array  $config  Renderer config array.
	 *
	 * @return  MustacheRenderer
	 *
	 * @since   2.0
	 */
	public function getMustacheRenderer($config = array())
	{
		return $this->getRenderer(static::ENGINE_MUSTACHE, $config);
	}

	/**
	 * Get a clone of global paths.
	 *
	 * @return  PriorityQueue
	 *
	 * @since   2.0
	 */
	public function getGlobalPaths()
	{
		return clone $this->getPaths();
	}

	/**
	 * Add a global path for Renderer search.
	 *
	 * @param   string  $path      The path you want to set.
	 * @param   int     $priority  Priority flag to order paths.
	 *
	 * @return  static
	 *
	 * @since   2.0
	 */
	public function addGlobalPath($path, $priority = PriorityQueue::LOW)
	{
		$this->getPaths()->insert($path, $priority);

		return $this;
	}

	/**
	 * An alias of getGlobalPath()
	 *
	 * @param   string  $path      The path you want to set.
	 * @param   int     $priority  Priority flag to order paths.
	 *
	 * @return  static
	 *
	 * @since   2.0
	 */
	public function addPath($path, $priority = PriorityQueue::LOW)
	{
		$this->addGlobalPath($path, $priority);

		return $this;
	}

	/**
	 * Get or create paths queue.
	 *
	 * @return  PriorityQueue
	 *
	 * @since   2.0
	 */
	protected function getPaths()
	{
		if (!$this->paths)
		{
			$this->paths = new PriorityQueue;

			$this->registerPaths();
		}

		return $this->paths;
	}

	/**
	 * Register default global paths.
	 *
	 * @return  static
	 *
	 * @since   2.0
	 */
	protected function registerPaths()
	{
		$config = $this->container->get('system.config');

		// Priority (1)
		$this->addPath(
			realpath($config->get('path.templates')),
			PriorityQueue::LOW - 20
		);

		// Priority (2)
		$this->addPath(
			realpath(__DIR__ . '/../Resources/Templates'),
			PriorityQueue::LOW - 30
		);

		return $this;
	}

	/**
	 * Reset all paths.
	 *
	 * @return  static
	 *
	 * @since   2.0
	 */
	public function reset()
	{
		$this->paths = null;

		return $this;
	}

	/**
	 * Method to set property paths
	 *
	 * @param   array $paths
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setPaths($paths)
	{
		if (!$paths instanceof PriorityQueue)
		{
			$paths = new PriorityQueue($paths);
		}

		$this->paths = $paths;

		return $this;
	}
}
