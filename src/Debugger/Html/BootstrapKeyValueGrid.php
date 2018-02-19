<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Html;

use Windwalker\Html\Grid\KeyValueGrid;

/**
 * The BootstrapKeyValueGrid class.
 *
 * @since  2.1.1
 */
class BootstrapKeyValueGrid extends KeyValueGrid
{
    /**
     * Class init.
     *
     * @param array $attribs
     */
    public function __construct($attribs = [])
    {
        if (!isset($attribs['class'])) {
            $attribs['class'] = 'table table-bordered';
        }

        parent::__construct($attribs);
    }

    /**
     * addHeader
     *
     * @param string $keyTitle
     * @param string $valueTitle
     * @param array  $attribs
     *
     * @return  static
     */
    public function addHeader($keyTitle = 'Key', $valueTitle = 'Value', $attribs = [])
    {
        if (!isset($attribs[static::COL_KEY]['width'])) {
            $attribs[static::COL_KEY]['width'] = '30%';
        }

        return parent::addHeader($keyTitle, $valueTitle, $attribs);
    }

    /**
     * addTitle
     *
     * @param string $name
     * @param array  $attribs
     *
     * @return  static
     */
    public function addTitle($name, $attribs = [])
    {
        if (!isset($attribs[static::ROW]['class'])) {
            $attribs[static::ROW]['class'] = 'active';
        }

        return parent::addTitle($name, $attribs);
    }
}
