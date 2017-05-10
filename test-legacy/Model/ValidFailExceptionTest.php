<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Test\Model;

use Windwalker\Core\Model\Exception\ValidateFailException;
use Windwalker\Test\TestCase\AbstractBaseTestCase;

/**
 * The ValidateFailExceptionTest class.
 *
 * @since  2.1.5.2
 */
class ValidateFailExceptionTest extends AbstractBaseTestCase
{
	/**
	 * Property instance.
	 *
	 * @var  ValidateFailException
	 */
	protected $instance;

	/**
	 * Sets up the fixture, for example, open a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{

	}

	/**
	 * testConstruct
	 *
	 * @return  void
	 */
	public function testConstruct()
	{
		$e = new ValidateFailException('foo');

		$this->assertEquals('foo', $e->getMessage());

		$e = new ValidateFailException(['a', 'b']);

		$this->assertStringSafeEquals("a\nb", $e->getMessage());

		$e = new ValidateFailException(
			[
			['a', 'b'],
			['c', 'd']
			]
		);

		$this->assertStringSafeEquals("a\nb\nc\nd", $e->getMessage());
	}
}
