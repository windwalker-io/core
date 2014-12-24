<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

/**
 * Interface WidgetInterface
 *
 * @since  {DEPLOY_VERSION}
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
