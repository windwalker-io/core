<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Finder;

/**
 * The RendererFinderInterface class.
 *
 * @since  3.0
 */
interface PackageFinderInterface
{
	/**
	 * find
	 *
	 * @param  string  $file
	 *
	 * @return string
	 */
	public function find(&$file);
}
