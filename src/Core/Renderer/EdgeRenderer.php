<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Renderer\Traits\GlobalVarsTrait;
use Windwalker\Core\Renderer\Traits\PackageFinderTrait;

/**
 * The EdgeRenderer class.
 *
 * @since  3.0
 */
class EdgeRenderer extends \Windwalker\Renderer\EdgeRenderer
{
	use PackageFinderTrait;
	use GlobalVarsTrait;
}
