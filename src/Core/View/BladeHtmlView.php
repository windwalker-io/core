<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Ioc;
use Windwalker\Renderer\BladeRenderer;

/**
 * Class TwigHtmlView
 *
 * @since 1.0
 */
class BladeHtmlView extends HtmlView
{
	/**
	 * Class init.
	 *
	 * @param array             $data
	 * @param BladeRenderer     $renderer
	 */
	public function __construct($data = array(), BladeRenderer $renderer = null)
	{
		$renderer = $renderer ? : new BladeRenderer;

		parent::__construct($data, $renderer);
	}
}
