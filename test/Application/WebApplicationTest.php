<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Application;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Http\Request\ServerRequestFactory;

/**
 * Test class of \Windwalker\Core\Application\WebApplication
 *
 * @since 2.1
 */
class WebApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test instance.
     *
     * @var WebApplication
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
        $this->instance = new WebApplication();
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
     * Method to test __construct().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::__construct
     * @TODO   Implement test__construct().
     */
    public function test__construct()
    {
        $app = new WebApplication(ServerRequestFactory::createFromUri(
            'http://windwalker.io/flower/sakura/new',
            '/flower/index.php'
        ));

        $uriExpected = [
            'full' => 'http://windwalker.io/flower/sakura/new',
            'current' => 'http://windwalker.io/flower/sakura/new',
            'script' => 'index.php',
            'root' => 'http://windwalker.io/flower/',
            'route' => 'sakura/new',
            'host' => 'http://windwalker.io',
            'path' => '/flower',
        ];

        $app->boot();

        $this->assertEquals($uriExpected, (array) $app->getServer()->uri);
    }

    /**
     * Method to test execute().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::execute
     */
    public function testExecute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test dispatch().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::dispatch
     * @TODO   Implement testDispatch().
     */
    public function testDispatch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test addMessage().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::addMessage
     * @TODO   Implement testAddMessage().
     */
    public function testAddMessage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test clearMessages().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::clearMessages
     * @TODO   Implement testClearMessages().
     */
    public function testClearMessages()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getMiddlewares().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getMiddlewares
     * @TODO   Implement testGetMiddlewares().
     */
    public function testGetMiddlewares()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test setMiddlewares().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::setMiddlewares
     * @TODO   Implement testSetMiddlewares().
     */
    public function testSetMiddlewares()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test addMiddleware().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::addMiddleware
     * @TODO   Implement testAddMiddleware().
     */
    public function testAddMiddleware()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test redirect().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::redirect
     * @TODO   Implement testRedirect().
     */
    public function testRedirect()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getMode().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getMode
     * @TODO   Implement testGetMode().
     */
    public function testGetMode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test setMode().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::setMode
     * @TODO   Implement testSetMode().
     */
    public function testSetMode()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test __get().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::__get
     * @TODO   Implement test__get().
     */
    public function test__get()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getPackage().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getPackage
     * @TODO   Implement testGetPackage().
     */
    public function testGetPackage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test addPackage().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::addPackage
     * @TODO   Implement testAddPackage().
     */
    public function testAddPackage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test triggerEvent().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::triggerEvent
     * @TODO   Implement testTriggerEvent().
     */
    public function testTriggerEvent()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getContainer().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getContainer
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
     * Method to test getName().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getName
     * @TODO   Implement testGetName().
     */
    public function testGetName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test isConsole().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::isConsole
     * @TODO   Implement testIsConsole().
     */
    public function testIsConsole()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test isWeb().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::isWeb
     * @TODO   Implement testIsWeb().
     */
    public function testIsWeb()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getDispatcher().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getDispatcher
     * @TODO   Implement testGetDispatcher().
     */
    public function testGetDispatcher()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test setDispatcher().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::setDispatcher
     * @TODO   Implement testSetDispatcher().
     */
    public function testSetDispatcher()
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
     * @covers Windwalker\Core\Application\WebApplication::setContainer
     * @TODO   Implement testSetContainer().
     */
    public function testSetContainer()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test getConfig().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::getConfig
     * @TODO   Implement testGetConfig().
     */
    public function testGetConfig()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test loadRouting().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::loadRouting
     * @TODO   Implement testLoadRouting().
     */
    public function testLoadRouting()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * Method to test prepareSystemPath().
     *
     * @return void
     *
     * @covers Windwalker\Core\Application\WebApplication::prepareSystemPath
     * @TODO   Implement testPrepareSystemPath().
     */
    public function testPrepareSystemPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
