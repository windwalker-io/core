<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\TwigRenderer;
use Windwalker\Utilities\Queue\Priority;

/**
 * Class TwigHtmlView
 *
 * @since 1.0
 */
class TwigHtmlView extends PhpHtmlView
{
	/**
	 * Property renderer.
	 *
	 * @var TwigRenderer
	 */
	protected $renderer;

	/**
	 * Class init.
	 *
	 * @param array             $data
	 * @param TwigRenderer      $renderer
	 */
	public function __construct($data = array(), TwigRenderer $renderer = null)
	{
		$renderer = $renderer ? : RendererHelper::getTwigRenderer();

		parent::__construct($data, $renderer);
	}
}
