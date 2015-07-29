<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
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
