<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Widget;

/**
 * Interface WidgetInterface
 *
 * @since  2.0
 */
interface WidgetInterface
{
	/**
	 * render
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function render($data = array());
}
