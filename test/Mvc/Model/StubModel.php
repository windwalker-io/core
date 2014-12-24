<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Mvc\Model;

use Windwalker\Core\Model\Model;

/**
 * The StubModel class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class StubModel extends Model
{
	/**
	 * getItem
	 *
	 * @return  string
	 */
	public function getItem()
	{
		return 'Item';
	}

	/**
	 * getList
	 *
	 * @return  array
	 */
	public function getList()
	{
		return array(1, 2, 3, 4);
	}
}
