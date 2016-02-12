<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Package\AbstractPackage;

/**
 * The BladeWidgetHelper class.
 *
 * @since  {DEPLOY_VERSION}
 */
class BladeWidgetHelper
{
	/**
	 * render
	 *
	 * @param string                  $layout
	 * @param array                   $data
	 * @param string|AbstractPackage  $package
	 *
	 * @return string
	 */
	public static function render($layout, $data = array(), $package = null)
	{
		return WidgetHelper::render($layout, $data, WidgetHelper::ENGINE_BLADE, $package);
	}
}
