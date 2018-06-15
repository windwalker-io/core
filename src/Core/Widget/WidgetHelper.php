<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The WidgetHelper class.
 *
 * @see    WidgetManager
 *
 * @method  static string  render($layout, $data = [], $engine = WidgetManager::ENGINE_PHP, $package = null)
 * @method  static Widget  createWidget($layout, $engine = null, $package = null)
 *
 * @since  3.0
 */
class WidgetHelper extends AbstractProxyFacade
{
    const PHP = 'php';

    const BLADE = 'blade';

    const EDGE = 'edge';

    const TWIG = 'twig';

    const MUSTACHE = 'mustache';

    /**
     * Property _key.
     *
     * @var  string
     */
    protected static $_key = 'widget.manager';
}
