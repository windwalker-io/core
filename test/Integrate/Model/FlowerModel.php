<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Integrate\Model;

use Windwalker\Core\Model\DatabaseModel;

/**
 * The StubModel class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class FlowerModel extends DatabaseModel
{
	/**
	 * getSakura
	 *
	 * @return  string
	 */
	public function getSakura()
	{
		return 'Sakura';
	}

	/**
	 * getFlower
	 *
	 * @return  string
	 */
	public function getFlower()
	{
		return 'Flower';
	}
}
