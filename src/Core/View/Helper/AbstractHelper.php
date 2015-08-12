<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Helper;

use Windwalker\Core\View\Helper\Set\HelperSet;

/**
 * Class AbstractHelper
 *
 * @since 1.0
 */
class AbstractHelper
{
	/**
	 * Property parent.
	 *
	 * @var  HelperSet
	 */
	protected $parent = null;

	/**
	 * Class init.
	 *
	 * @param HelperSet $parent
	 */
	public function __construct(HelperSet $parent = null)
	{
		$this->parent = $parent;
	}

	/**
	 * Method to get property Parent
	 *
	 * @return  HelperSet
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Method to set property parent
	 *
	 * @param   HelperSet $parent
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;

		return $this;
	}
}
