<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\System;

use Windwalker\Debugger\View\AbstractDebuggerHtmlView;
use Windwalker\Dom\HtmlElement;
use function Windwalker\h;

/**
 * The SystemHtmlView class.
 *
 * @since  2.1.1
 */
class SystemHtmlView extends AbstractDebuggerHtmlView
{
    /**
     * prepareData
     *
     * @param \Windwalker\Data\Data $data
     *
     * @return  void
     */
    protected function prepareData($data)
    {
        $data->collector = $data->item['collector'];

        $customData = [];

        foreach ((array) $data->collector['custom.data'] as $key => $item) {
            if (is_array($item)) {
                $item = (string) h('pre', [], print_r($item, 1));
            }

            $customData[$key] = $item;
        }

        $data->collector['custom.data'] = $customData;
    }
}
