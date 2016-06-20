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
 * @since  {DEPLOY_VERSION}
 */
class EdgeRenderer extends \Windwalker\Renderer\EdgeRenderer
{
	use PackageFinderTrait;
	use GlobalVarsTrait;
}
