<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Config;

use Windwalker\Structure\Structure;

/**
 * The ConfigRegistry class.
 *
 * @since  3.0
 */
class Config extends Structure
{
//	/**
//	 * Method to recursively bind data to a parent object.
//	 *
//	 * @param   array   $parent The parent object on which to attach the data values.
//	 * @param   mixed   $data   An array or object of data to bind to the parent object.
//	 * @param   boolean $raw    Set to false to convert all object to array.
//	 *
//	 * @return  void
//	 */
//	protected function bindData(&$parent, $data, $raw = false)
//	{
//		// Ensure the input data is an array.
//		if (!$raw)
//		{
//			$data = StructureHelper::toArray($data, true);
//		}
//
//		foreach ($data as $key => $value)
//		{
//			if (in_array($value, $this->ignoreValues, true))
//			{
//				continue;
//			}
//
//			if (is_array($value))
//			{
//				if (!isset($parent[$key]))
//				{
//					$parent[$key] = array();
//				}
//
//				$this->bindData($parent[$key], $value);
//			}
//			else
//			{
//				$value = $this->loadVariable($value);
//
//				$parent[$key] = $value;
//			}
//		}
//	}
//
//	protected function loadVariable($value)
//	{
//		if (strpos($value, '\\@') === 0)
//		{
//			$value = substr($value, 1);
//		}
//		elseif (strpos($value, '@') === 0)
//		{
//			$value = substr($value, 1);
//
//			$value = $this->get($value);
//		}
//
//		return $value;
//	}
//
//	public static function replaceVariables(Structure $registry)
//	{
//		$array = static::doReplaceVariables($registry, $registry->toArray());
//	}
//
//	protected static function doReplaceVariables(Structure $registry, $array)
//	{
//		foreach ($array as $key => &$value)
//		{
//			if (is_array($value))
//			{
//				static::doReplaceVariables($registry, $array);
//			}
//			elseif (is_string($value))
//			{
//				if (strpos($value, '\\@') === 0)
//				{
//					$value = substr($value, 1);
//				}
//				elseif (strpos($value, '@') === 0)
//				{
//					$value = substr($value, 1);
//
//					$value = $registry->get($value);
//				}
//			}
//		}
//
//		return $array;
//	}
//
//	public static function getVariablePath($value)
//	{
//
//	}
}
