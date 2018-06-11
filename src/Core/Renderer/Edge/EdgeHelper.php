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
 * @since  3.3
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
     * @since  3.3
     */
    public static function attr($name, $value = null)
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        return HtmlBuilder::buildAttributes($name);
    }
}
