<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\PaginationPackage\Test;

use Windwalker\Core\Test\AbstractBaseTestCase;
use Windwalker\PaginationPackage\Pagination;

/**
 * The PaginationTest class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class PaginationTest extends AbstractBaseTestCase
{
	/**
	 * pageProvider
	 *
	 * @return  array
	 */
	public function pageProvider()
	{
		return array(
			array(
				500,
				1,
				10,
				2,
				array(
					1 => 'current',
					2 => 'higher',
					3 => 'higher',
					4 => 'more',
					50 => 'last',
				)
			),
			array(
				500,
				3,
				10,
				2,
				array(
					1 => 'lower',
					2 => 'lower',
					3 => 'current',
					4 => 'higher',
					5 => 'higher',
					6 => 'more',
					50 => 'last',
				)
			),
			array(
				500,
				10,
				10,
				2,
				array(
					1 => 'first',
					7 => 'less',
					8 => 'lower',
					9 => 'lower',
					10 => 'current',
					11 => 'higher',
					12 => 'higher',
					13 => 'more',
					50 => 'last',
				)
			),
			array(
				500,
				48,
				10,
				2,
				array(
					1 => 'first',
					45 => 'less',
					46 => 'lower',
					47 => 'lower',
					48 => 'current',
					49 => 'higher',
					50 => 'higher',
				)
			),
		);
	}

	/**
	 * testBuild
	 *
	 * @param $total
	 * @param $current
	 * @param $perPage
	 * @param $neighbours
	 *
	 * @return  void
	 *
	 * @dataProvider pageProvider
	 */
	public function testBuild($total, $current, $perPage, $neighbours, $output)
	{
		$pagination = new Pagination($total, $current, $perPage, $neighbours);

		$result = $pagination->getResult();

		$this->assertEquals($output, $result->getAll());
	}
}
