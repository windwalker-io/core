<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Authenticate;

use Windwalker\Data\Data;

/**
 * The UserHandlerInterface class.
 * 
 * @since  {DEPLOY_VERSION}
 */
interface UserHandlerInterface
{
	/**
	 * load
	 *
	 * @param array $conditions
	 *
	 * @return  mixed
	 */
	public function load($conditions);

	/**
	 * save
	 *
	 * @param Data $data
	 *
	 * @return  Data
	 */
	public function save(Data $data);

	/**
	 * delete
	 *
	 * @param array $conditions
	 *
	 * @return  boolean
	 */
	public function delete($conditions);
}
