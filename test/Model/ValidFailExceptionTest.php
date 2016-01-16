<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Test\Model;

use Windwalker\Core\Model\Exception\ValidFailException;
use Windwalker\Test\TestCase\AbstractBaseTestCase;

/**
 * The ValidFailExceptionTest class.
 *
 * @since  2.1.5.2
 */
class ValidFailExceptionTest extends AbstractBaseTestCase
{
	/**
	 * Property instance.
	 *
	 * @var  ValidFailException
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
		$e = new ValidFailException('foo');

		$this->assertEquals('foo', $e->getMessage());

		$e = new ValidFailException(array('a', 'b'));

		$this->assertStringSafeEquals("a\nb", $e->getMessage());

		$e = new ValidFailException(array(
			array('a', 'b'),
			array('c', 'd')
		));

		$this->assertStringSafeEquals("a\nb\nc\nd", $e->getMessage());
	}
}
