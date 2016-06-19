<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authentication;

use Windwalker\Data\DataInterface;

/**
 * The UserDataInterface class.
 * 
 * @since  2.0
 */
interface UserDataInterface extends DataInterface
{
	/**
	 * isGuest
	 *
	 * @return  boolean
	 */
	public function isGuest();

	/**
	 * isMember
	 *
	 * @return  boolean
	 */
	public function isMember();
}
