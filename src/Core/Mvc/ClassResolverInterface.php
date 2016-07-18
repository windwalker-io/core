<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

/**
 * Interface ClassResolverInterface
 *
 * @since  3.0
 */
interface ClassResolverInterface
{
	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix();

	/**
	 * Resolve class path.
	 *
	 * @param   string $name
	 *
	 * @return false|string
	 * @internal param string|AbstractPackage $package
	 *
	 */
	public function resolve($name);
}
