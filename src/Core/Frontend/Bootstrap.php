<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Frontend;

use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\Core\Widget\WidgetManager;

/**
 * The Bootstrap class.
 *
 * @since  2.0.9
 */
class Bootstrap
{
    const MSG_SUCCESS = 'success';

    const MSG_INFO = 'info';

    const MSG_WARNING = 'warning';

    const MSG_DANGER = 'danger';

    /**
     * renderFields
     *
     * @param array  $fields
     * @param string $labelCols
     * @param string $inputCols
     * @param string $tmpl
     *
     * @return  string
     */
    public static function renderFields(
        array $fields,
        $labelCols = 'col-md-3',
        $inputCols = 'col-md-9',
        $tmpl = 'bootstrap.form.fields'
    ) {
        return WidgetHelper::render($tmpl, [
            'fields' => $fields,
            'label_cols' => $labelCols,
            'input_cols' => $inputCols,
        ]);
    }
}
