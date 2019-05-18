<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\View\Helper\AbstractHelper;
use Windwalker\Renderer\AbstractRenderer;
use Windwalker\Renderer\MustacheRenderer;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * RendererHelper.
 *
 * @see    RendererManager
 *
 * @method static  AbstractRenderer  getRenderer($type = 'php', $config = [])
 * @method static  PhpRenderer      getPhpRenderer($config = [])
 * @method static  BladeRenderer    getBladeRenderer($config = [])
 * @method static  EdgeRenderer     getEdgeRenderer($config = [])
 * @method static  TwigRenderer     getTwigRenderer($config = [])
 * @method static  MustacheRenderer  getMustacheRenderer($config = [])
 * @method static  PriorityQueue    getGlobalPaths()
 * @method static  RendererManager  addGlobalPath($path, $priority = PriorityQueue::LOW)
 * @method static  RendererManager  addPath($path, $priority = PriorityQueue::LOW)
 * @method static  RendererManager  reset()
 * @method static  RendererManager  setPaths($paths)
 * @method static  RendererManager  addHelper($name, $helper)
 * @method static  AbstractHelper   getHelper($name)
 * @method static  void             getHelpers()
 * @method static  RendererManager  setHelpers($helpers)
 * @method static  RendererManager  addGlobal($name, $value)
 * @method static  RendererManager  removeGlobal($name)
 * @method static  array            getGlobals()
 * @method static  RendererManager  setGlobals($globals)
 *
 * @since  2.0
 */
abstract class RendererHelper extends AbstractProxyFacade
{
    public const PHP = 'php';

    public const BLADE = 'blade';

    public const EDGE = 'edge';

    public const TWIG = 'twig';

    public const MUSTACHE = 'mustache';

    protected static $_key = 'renderer.manager';

    /**
     * Boot RendererManager.
     *
     * @return  void
     */
    public static function boot()
    {
        static::getInstance();
    }
}
