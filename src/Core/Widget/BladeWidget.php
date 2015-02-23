<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Widget;

use Windwalker\Core\Ioc;
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
	 * @param string  $layout
	 */
	public function __construct($layout)
	{
		parent::__construct($layout, new BladeRenderer(null, array('cache_path' => Ioc::getConfig()->get('path.cache') . '/view')));
	}
}
