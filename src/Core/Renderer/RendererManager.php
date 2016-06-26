<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Renderer\Traits\GlobalVarsTrait;
use Windwalker\Core\View\Helper\AbstractHelper;
use Windwalker\DI\Container;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Renderer\MustacheRenderer;
use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\PhpRenderer;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The RendererFactory class.
 *
 * @since  {DEPLOY_VERSION}
 */
class RendererManager
{
	use GlobalVarsTrait;

	const PHP      = 'php';
	const BLADE    = 'blade';
	const EDGE     = 'edge';
	const TWIG     = 'twig';
	const MUSTACHE = 'mustache';

	/**
	 * A PriorityQueue which extends the SplPriorityQueue.
	 *
	 * @var  PriorityQueue.
	 *
	 * @since  2.0
	 */
	protected $paths;

	/**
	 * Property helpers.
	 *
	 * @var  AbstractHelper[]
	 */
	protected $helpers = [];

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
	public function getRenderer($type = self::PHP, $config = array())
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
				$config = $this->container->get('config');

				$config['cache_path'] = $config->get('path.cache') . '/renderer';
			}
		}

		if ($type == 'edge')
		{
			if (empty($config['cache_path']) && !isset($config['cache']))
			{
				$config = $this->container->get('config');

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

		/** @var AbstractRenderer|CoreRendererInterface|GlobalVarsTrait $renderer */
		$renderer = new $class(static::getGlobalPaths(), $config);

		$renderer->setPackageFinder($this->container->get('package.finder'));
		$renderer->setGlobals($this->getGlobals());

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
		return $this->getRenderer(static::PHP, $config);
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
		return $this->getRenderer(static::BLADE, $config);
	}

	/**
	 * getEdgeRenderer
	 *
	 * @param array $config
	 *
	 * @return  EdgeRenderer
	 *
	 * @since   3.0
	 */
	public function getEdgeRenderer($config = array())
	{
		return $this->getRenderer(static::EDGE, $config);
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
		return $this->getRenderer(static::TWIG, $config);
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
		return $this->getRenderer(static::MUSTACHE, $config);
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
		$config = $this->container->get('config');

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

	/**
	 * addHelper
	 *
	 * @param   string $name
	 * @param   object $helper
	 *
	 * @return  static
	 */
	public function addHelper($name, $helper)
	{
		$this->helpers[$name] = $helper;

		return $this;
	}

	/**
	 * getHelper
	 *
	 * @param   string  $name
	 *
	 * @return  AbstractHelper|object
	 */
	public function getHelper($name)
	{
		if (empty($this->helpers[$name]))
		{
			return null;
		}

		return $this->helpers[$name];
	}

	/**
	 * Method to get property Helpers
	 *
	 * @return  \Windwalker\Core\View\Helper\AbstractHelper[]
	 */
	public function getHelpers()
	{
		return $this->helpers;
	}

	/**
	 * Method to set property helpers
	 *
	 * @param   \Windwalker\Core\View\Helper\AbstractHelper[] $helpers
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setHelpers($helpers)
	{
		$this->helpers = $helpers;

		return $this;
	}
}
