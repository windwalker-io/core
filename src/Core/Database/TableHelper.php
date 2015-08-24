<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database;

use Windwalker\Core\Ioc;

/**
 * The TableHelper class.
 * 
 * @since  2.1.1
 */
class TableHelper
{
	/**
	 * stripPrefix
	 *
	 * @param string $table
	 * @param string $prefix
	 *
	 * @return  string
	 */
	public static function stripPrefix($table, $prefix = null)
	{
		$prefix = $prefix ?: Ioc::getDatabase()->getPrefix();

		$num = strlen($prefix);

		if (substr($table, 0, $num) == $prefix)
		{
			$table = '#__' . substr($table, $num);
		}

		return $table;
	}
}
