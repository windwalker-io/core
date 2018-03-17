<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Renderer\Edge;

use Windwalker\Dom\Builder\HtmlBuilder;

/**
 * The EdgeHelper class.
 *
 * @since  __DEPLOY_VERSION__
 */
class EdgeHelper
{
    /**
     * attr
     *
     * @param string|array $name
     * @param mixed        $value
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function attr($name, $value = null)
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        return HtmlBuilder::buildAttributes($name);
    }
}
