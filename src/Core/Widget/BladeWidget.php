<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Ioc;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Renderer\BladeRenderer;

/**
 * The BladeWidget class.
 * 
 * @since  2.0
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
	 * @param string $layout
	 * @param string $package
	 */
	public function __construct($layout, $package = null)
	{
		parent::__construct(
			$layout,
			RendererHelper::getBladeRenderer(),
			$package
		);
	}
}
