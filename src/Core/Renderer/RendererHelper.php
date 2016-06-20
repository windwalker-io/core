<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * RendererHelper.
 *
 * @see  RendererManager
 *
 * @method  static  AbstractRenderer|CoreRendererInterface  getRenderer($type = RendererManager::ENGINE_PHP, $config = array())
 * @method  static  PhpRenderer      getPhpRenderer($config = array())
 * @method  static  BladeRenderer    getBladeRenderer($config = array())
 * @method  static  TwigRenderer     getTwigRenderer($config = array())
 * @method  static  PriorityQueue    getGlobalPaths()
 * @method  static  RendererManager  addGlobalPath($path, $priority = PriorityQueue::LOW)
 * @method  static  RendererManager  addPath($path, $priority = PriorityQueue::LOW)
 * @method  static  PriorityQueue    getPaths()
 * @method  static  RendererManager  reset()
 * @method  static  RendererManager  setPaths($paths)
 *
 * @since  2.0
 */
abstract class RendererHelper extends AbstractProxyFacade
{
	const ENGINE_PHP      = 'php';
	const ENGINE_BLADE    = 'blade';
	const ENGINE_EDGE     = 'edge';
	const ENGINE_TWIG     = 'twig';
	const ENGINE_MUSTACHE = 'mustache';

	protected static $_key = 'renderer.manager';
}
