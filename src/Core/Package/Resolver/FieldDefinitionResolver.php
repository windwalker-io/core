<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package\Resolver;

use Windwalker\Form\FieldDefinitionInterface;

/**
 * The FormDefinitionResolver class.
 *
 * @since  {DEPLOY_VERSION}
 */
class FieldDefinitionResolver extends AbstractPackageObjectResolver
{
	/**
	 * createObject
	 *
	 * @param  string $class
	 * @param  array  $args
	 *
	 * @return FieldDefinitionInterface
	 * @throws \InvalidArgumentException
	 */
	protected static function createObject($class, ...$args)
	{
		if (!is_subclass_of($class, 'Windwalker\Form\FieldDefinitionInterface'))
		{
			throw new \InvalidArgumentException(sprintf('Class: %s is not sub class of Windwalker\Form\FieldDefinitionInterface', $class));
		}

		return new $class(...$args);
	}

	/**
	 * getClass
	 *
	 * @param string $name
	 *
	 * @return  string
	 */
	public static function getClass($name)
	{
		return ucfirst($name) . 'Definition';
	}
}
