<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The DocblockHelper class.
 *
 * @since  {DEPLOY_VERSION}
 */
class DocblockHelper
{
	/**
	 * listVarTypes
	 *
	 * @param array $data
	 *
	 * @return  string
	 */
	public static function listVarTypes(array $data)
	{
		$vars = [];

		foreach ($data as $key => $value)
		{
			if (is_object($value))
			{
				$type = '\\' . get_class($value);
			}
			else
			{
				$type = gettype($value);
			}

			$vars[] = sprintf(' * @var  $%s  %s', $key, $type);
		}

		$tmpl = <<<TMPL
/**
%s
 */
TMPL;

		return sprintf($tmpl, implode("\n", $vars));
	}
}
