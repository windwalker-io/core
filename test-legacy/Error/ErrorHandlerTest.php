<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Error;

use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Test\TestCase\AbstractBaseTestCase;

/**
 * Test class of ErrorHandler
 *
 * @since 2.1.1
 */
class ErrorHandlerTest extends AbstractBaseTestCase
{
	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::register
	 */
	protected function setUp()
	{
		StubErrorHandler::register();
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::restore
	 */
	protected function tearDown()
	{
		StubErrorHandler::restore();
	}

	/**
	 * Method to test error().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::error
	 */
	public function testError()
	{
		StubErrorHandler::register();

		trigger_error('Test Error', E_USER_NOTICE);

		$response = StubErrorHandler::$response;

		$file = __FILE__;
		$code = E_USER_NOTICE;
		$line = __LINE__ - 6;

		$compare = <<<TXT
Message: Test Error. File: {$file} (line: {$line})
Code: {$code}
File: {$file}
Line: {$line}
TXT;

		$this->assertStringSafeEquals($compare, $response->getBody());

		$headers = $response->getHeaders();
		$this->assertEquals($code, $headers[0]['value']);
	}

	/**
	 * Method to test exception().
	 *
	 * @throws \Exception
	 * @return void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::exception
	 */
	public function testException()
	{
		$exception = new \Exception('Test Exception', 123);

		StubErrorHandler::exception($exception);

		$response = StubErrorHandler::$response;

		$file = __FILE__;
		$code = 123;
		$line = __LINE__ - 8;

		$compare = <<<TXT
Message: Test Exception
Code: {$code}
File: {$file}
Line: {$line}
TXT;

		$this->assertStringSafeEquals($compare, $response->getBody());
	}

	/**
	 * testSetErrorTemplate
	 *
	 * @param string $tmpl
	 *
	 * @return  void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::setErrorTemplate
	 */
	public function testSetErrorTemplate()
	{
		StubErrorHandler::setErrorTemplate('flower.error.test');

		trigger_error('Test');

		$response = StubErrorHandler::$response;

		$this->assertStringSafeEquals('Test Error Template', $response->getBody());
	}

	/**
	 * testGetLevelName
	 *
	 * @return  void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::getLevelName
	 */
	public function testGetLevelName()
	{
		$this->assertEquals('E_STRICT', ErrorHandler::getLevelName(E_STRICT));
	}

	/**
	 * testGetLevelCode
	 *
	 * @return  void
	 *
	 * @covers Windwalker\Core\Error\ErrorHandler::getLevelCode
	 */
	public function testGetLevelCode()
	{
		$this->assertEquals(E_WARNING, ErrorHandler::getLevelCode('E_WARNING'));
		$this->assertFalse(ErrorHandler::getLevelCode(9999.999));
	}
}
