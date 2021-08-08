<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    MIT
 */

namespace Windwalker\Core\Renderer\Edge;

use Windwalker\Dom\Builder\HtmlBuilder;
use Windwalker\DOM\DOMElement;

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
    public static function attr(string|array $name, mixed $value = null): string
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        return DOMElement::buildAttributes($name);
    }
}
