<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Asset class.
 *
 * @see    AssetManager
 *
 * phpcs:disable
 *
 * @method  static AssetManager  addStyle()                addStyle($url, array $options = [], array $attribs = [])
 * @method  static AssetManager  addScript()               addScript($url, array $options = [], array $attribs = [])
 * @method  static AssetManager  internalStyle()           internalStyle($content)
 * @method  static AssetManager  internalScript()          internalScript($content)
 * @method  static AssetManager  addCSS()                  addCSS($url, array $options = [], array $attribs = [])
 * @method  static AssetManager  addJS()                   addJS($url, array $options = [], array $attribs = [])
 * @method  static AssetManager  import()                  import($url, array $options = [], array $attribs = [])
 * @method  static AssetManager  internalCSS()             internalCSS($content)
 * @method  static AssetManager  internalJS()              internalJS($content)
 * @method  static string|false  exists()                  exists(string $uri, bool $strict = false)
 * @method  static string        renderStyles()            renderStyles($withInternal = false, array $internalAttrs = [])
 * @method  static string        renderScripts()           renderScripts($withInternal = false, array $internalAttrs = [])
 * @method  static string        renderInternalStyles()    renderInternalStyles()
 * @method  static string        renderInternalScripts()   renderInternalScripts()
 * @method  static AssetManager  alias()                   alias($target, $alias, array $options = [], array $attrs = [])
 * @method  static string        resolveAlias()            resolveAlias($alias)
 * @method  static array         resolveRawAlias()         resolveRawAlias($alias)
 * @method  static string        getVersion()              getVersion()
 * @method  static AssetManager  setIndents()              setIndents(string $indents)
 * @method  static string        getIndents()              getIndents()
 * @method  static AssetTemplate getTemplate()             getTemplate()
 * @method  static string        setTemplate()             setTemplate(AssetTemplate $template)
 * @method  static string        getJSObject()             getJSObject(array $array)
 * @method  static string        path()                    path($uri = null, $version = false)
 * @method  static string        root()                    root($uri = null, $version = false)
 *
 * phpcs:enable
 *
 * @since  1.0
 */
abstract class Asset extends AbstractProxyFacade
{
    /**
     * Property key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'asset';
    // phpcs:enable
}
