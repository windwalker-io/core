<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Model\ModelRepository;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ModelResolver class.
 *
 * @since  3.0
 */
class ModelResolver extends AbstractClassResolver
{
	/**
	 * Property baseClass.
	 *
	 * @var  string
	 */
	protected $baseClass = ModelRepository::class;

	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix()
	{
		return 'model';
	}

	/**
	 * If didn't found any exists class, fallback to default class which in current package..
	 *
	 * @return string Found class name.
	 */
	protected function getDefaultNamespace()
	{
		return ReflectionHelper::getNamespaceName($this->package) . '\Model';
	}
}
