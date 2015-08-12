<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Test\Cache;

use Windwalker\Cache\Cache;
use Windwalker\Cache\DataHandler\StringHandler;
use Windwalker\Cache\Storage\FileStorage;
use Windwalker\Cache\Storage\NullStorage;
use Windwalker\Cache\Storage\RuntimeStorage;
use Windwalker\Core\Cache\CacheFactory;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;

/**
 * Test class of CacheFactory
 *
 * @since {DEPLOY_VERSION}
 */
class CacheFactoryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var CacheFactory
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
		$this->instance = new CacheFactory;

		$config = Ioc::getConfig();

		$config->set('system.debug', false);
		$config->set('cache.enabled', true);
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
	 * Method to test create().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::create
	 */
	public function testCreate()
	{
		$config = Ioc::getConfig();
		$cache = $this->instance->create();

		// Test correct type.
		$this->assertTrue($cache instanceof Cache);

		$this->assertTrue($cache->getStorage() instanceof RuntimeStorage);

		// Test singleton
		$this->assertSame($cache, $this->instance->create('windwalker'));
		$this->assertNotSame($cache, $this->instance->create('foo'));

		// Test handler
		$this->assertTrue($this->instance->create('windwalker', null, 'string')->getHandler() instanceof StringHandler);

		// Test storage
		$this->assertTrue($this->instance->create('windwalker', 'file', 'string', array('cache_dir' => WINDWALKER_CACHE))->getStorage() instanceof FileStorage);

		// Test FileStorage Denycode
		$config->set('cache.denyAccess', true);
		$fileStorage = $this->instance->create('windwalker', 'file', 'string', array('cache_dir' => WINDWALKER_CACHE, 'deny_code' => 'FOO'))->getStorage();

		$optinos = $fileStorage->getOptions();

		$this->assertEquals('FOO', $optinos['deny_code']);
	}

	/**
	 * Method to test create().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::create
	 */
	public function testCreateIfDebug()
	{
		$config = Ioc::getConfig();

		// Debug true, Enabled true
		$config['system.debug'] = true;
		$config['cache.enabled'] = true;

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof NullStorage);

		// Debug true, Enabled false
		$config['system.debug'] = true;
		$config['cache.enabled'] = false;

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof NullStorage);

		// Ignore global
		$config['system.debug'] = true;
		$config['cache.enabled'] = true;

		$this->instance->ignoreGlobal(true);

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof RuntimeStorage);
	}

	/**
	 * Method to test create().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::create
	 */
	public function testCreateIfNotEnabled()
	{
		$config = Ioc::getConfig();

		// Debug true, Enabled true
		$config['system.debug'] = false;
		$config['cache.enabled'] = false;

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof NullStorage);

		// Debug true, Enabled false
		$config['system.debug'] = true;
		$config['cache.enabled'] = false;

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof NullStorage);

		// Ignore global
		$config['system.debug'] = false;
		$config['cache.enabled'] = false;

		$this->instance->ignoreGlobal(true);

		$cache = $this->instance->create();

		$this->assertTrue($cache->getStorage() instanceof RuntimeStorage);
	}

	/**
	 * Method to test getCache().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::getCache
	 * @TODO   Implement testGetCache().
	 */
	public function testGetCache()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getStorage().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::getStorage
	 * @TODO   Implement testGetStorage().
	 */
	public function testGetStorage()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Method to test getDataHandler().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Cache\CacheFactory::getDataHandler
	 * @TODO   Implement testGetDataHandler().
	 */
	public function testGetDataHandler()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
