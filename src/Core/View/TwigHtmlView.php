<?php
/**
 * Part of formosa project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Renderer\TwigRenderer;

/**
 * Class TwigHtmlView
 *
 * @since 1.0
 */
class TwigHtmlView extends HtmlView
{
	/**
	 * Class init.
	 *
	 * @param array             $data
	 * @param TwigRenderer      $renderer
	 */
	public function __construct($data = array(), TwigRenderer $renderer = null)
	{
		$renderer = $renderer ? : new TwigRenderer;

		parent::__construct($data, $renderer);
	}
}
 