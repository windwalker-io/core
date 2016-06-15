<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

use Windwalker\Utilities\Classes\TraitHelper;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The BootableTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait BootableTrait
{
	/**
	 * bootTraits
	 *
	 * @param array $args
	 */
	protected function bootTraits(...$args)
	{
		foreach (TraitHelper::classUsesRecursive($this) as $trait)
		{
			$method = 'boot' . ReflectionHelper::getShortName($trait);

			if (method_exists($this, $method))
			{
				call_user_func_array([$this, $method], $args);
			}
		}
	}
}
