<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Security;

use Windwalker\Core\Ioc;
use Windwalker\Core\Security\CsrfGuard;
use Windwalker\Core\Security\CsrfProtection;
use Windwalker\IO\Input;
use Windwalker\Session\Session;

/**
 * Test class of CsrfGuard
 *
 * @since {DEPLOY_VERSION}
 */
class CsrfGuardTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var CsrfGuard
	 */
	protected $instance;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$this->instance = new CsrfGuard(Ioc::factory());
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
	}

	/**
	 * Method to test validate().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::validate
	 * @TODO   Implement testValidate().
	 */
	public function testValidate()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test input().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::input
	 */
	public function testInput()
	{
		$token = $this->instance->getFormToken(123);

		$html = sprintf('<input class="test" type="hidden" name="%s" value="1" />', $token);

		$this->assertEquals($html, (string) $this->instance->input(123, array('class' => 'test')));
	}

	/**
	 * Method to test createToken().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::createToken
	 */
	public function testCreateToken()
	{
		$this->markTestSkipped(
			'This is random return value so we don\'t test it.'
		);
	}

	/**
	 * Method to test getToken().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::getToken
	 */
	public function testGetToken()
	{
		/** @var Session $session */
		$session = $this->instance->getContainer()->get('system.session');
		$token = $this->instance->getToken(true);

		$this->assertEquals(
			$session->get(CsrfGuard::TOKEN_KEY),
			$token
		);

		$this->assertEquals(
			$session->get(CsrfGuard::TOKEN_KEY),
			CsrfProtection::getToken()
		);
	}

	/**
	 * Method to test getFormToken().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::getFormToken
	 */
	public function testGetFormToken()
	{
		$token = $this->instance->getFormToken(123, true);
		$container = $this->instance->getContainer();
		$config = $container->get('config');

		$this->assertEquals(
			md5($config['system.secret'] . 123 . $this->instance->getToken()),
			$token
		);
	}

	/**
	 * Method to test checkToken().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::checkToken
	 */
	public function testCheckToken()
	{
		/** @var Input $input */
		$input = $this->instance->getContainer()->get('system.input');

		$input->set($this->instance->getFormToken(213), 1);

		$this->assertTrue($this->instance->checkToken(213));

		$input->set($this->instance->getFormToken(213), null);
	}

	/**
	 * Method to test getContainer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::getContainer
	 * @TODO   Implement testGetContainer().
	 */
	public function testGetContainer()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test setContainer().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Security\CsrfGuard::setContainer
	 * @TODO   Implement testSetContainer().
	 */
	public function testSetContainer()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
