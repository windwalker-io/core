<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\View\Helper;

/**
 * The GridHelper class.
 * 
 * @since  2.0
 */
class GridHelper extends AbstractHelper
{
	/**
	 * getBooleanIcon
	 *
	 * @param integer $value
	 *
	 * @return  string
	 */
	public function getBooleanIcon($value)
	{
		$icon = $value ? 'ok-sign' : 'remove-sign';

		$color = $value ? 'text-success' : 'text-danger';

		return '<span class="glyphicon glyphicon-' . $icon . ' ' . $color . '"></span>';
	}
}
