<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Error;

use Windwalker\Core\Test\AbstractBaseTestCase;

/**
 * Test class of ErrorHandler
 *
 * @since {DEPLOY_VERSION}
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
}
