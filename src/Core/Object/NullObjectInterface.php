<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Object;

/**
 * The Null Object Interface
 */
interface NullObjectInterface extends SilencerInterface
{
	/**
	 * Is this object not contain any values.
	 *
	 * @return boolean
	 */
	public function isNull();

	/**
	 * Is this object not contain any values.
	 *
	 * @return  boolean
	 */
	public function notNull();
}
