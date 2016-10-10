<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

use Windwalker\Utilities\Classes\TraitHelper;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The BootableTrait class.
 *
 * @since  3.0
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
				$this->$method(...$args);
			}
		}
	}
}
