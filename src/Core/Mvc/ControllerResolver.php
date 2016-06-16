<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Mvc;

use Windwalker\Core\Controller\Controller;
use Windwalker\Utilities\Reflection\ReflectionHelper;

/**
 * The ControllerResolver class.
 *
 * @since  2.1.1
 */
class ControllerResolver extends AbstractClassResolver
{
	/**
	 * Property baseClass.
	 *
	 * @var  string
	 */
	protected $baseClass = Controller::class;

	/**
	 * Get container key prefix.
	 *
	 * @return  string
	 */
	public static function getPrefix()
	{
		return 'controller';
	}

	/**
	 * getDefaultNamespace
	 *
	 * @return  string
	 */
	protected function getDefaultNamespace()
	{
		return ReflectionHelper::getNamespaceName($this->getPackage()) . '\Controller';
	}
}
