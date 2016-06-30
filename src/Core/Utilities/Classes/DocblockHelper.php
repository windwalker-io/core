<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

use Windwalker\Utilities\Reflection\ReflectionHelper;

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

		return static::renderDocblock(implode("\n", $vars));
	}

	/**
	 * listMethods
	 *
	 * @param mixed $class
	 * @param int   $type
	 *
	 * @return  string
	 */
	public static function listMethods($class, $type = \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC)
	{
		$methods = ReflectionHelper::getMethods($class, $type);

		$lines = [];

		/** @var \ReflectionMethod $method */
		foreach ($methods as $method)
		{
			preg_match('/\s+\*\s+@return\s+([\w]+)\s*[\w ]*/', $method->getDocComment(), $matches);

			$return = isset($matches[1]) ? $matches[1] : 'void';

			if ($return == 'static' || $return == 'self' || $return == '$this')
			{
				$return = $method->getDeclaringClass()->getName();
			}

			$source = file($method->getFileName());
			$body = implode("", array_slice($source, $method->getStartLine() - 1, 1));

			preg_match('/\s+public\s+[static]*\s*function\s+(.*)/', $body, $matches);
			$body = $matches[1];

			$lines[] = sprintf(' * @method  %s  %s', $return, $body);
		}

		return static::renderDocblock(implode("\n", $lines));
	}

	/**
	 * renderDocblock
	 *
	 * @param   string  $content
	 *
	 * @return  string
	 */
	public static function renderDocblock($content)
	{
		$tmpl = <<<TMPL
/**
%s
 */
TMPL;

		return sprintf($tmpl, $content);
	}
}
