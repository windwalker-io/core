<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

use Windwalker\Renderer\BladeRenderer;
use Windwalker\Renderer\RendererInterface;

/**
 * The BladeWidget class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class BladeWidget extends Widget
{
	/**
	 * Property renderer.
	 *
	 * @var BladeRenderer
	 */
	protected $renderer;

	/**
	 * Class init.
	 *
	 * @param string  $layout
	 */
	public function __construct($layout)
	{
		parent::__construct($layout, new BladeRenderer);
	}
}
