<?php
/**
 * Part of Windwalker project Test files.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Test\Utilities;

use Windwalker\Core\Utilities\Iterator\PriorityQueue;
use Windwalker\Utilities\Queue\Priority;

/**
 * Test class of PriorityQueue
 *
 * @since {DEPLOY_VERSION}
 */
class PriorityQueueTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test instance.
	 *
	 * @var PriorityQueue
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
		$this->instance = new PriorityQueue;

		$this->instance->insert('a', Priority::LOW);
		$this->instance->insert('b', Priority::LOW);
		$this->instance->insert('c', Priority::LOW);
		$this->instance->insert('d', Priority::LOW);
		$this->instance->insert('e', Priority::LOW);
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
	 * Method to test insert().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Utilities\Iterator\PriorityQueue::insert
	 */
	public function testInsert()
	{
		$this->assertEquals(array('a', 'b', 'c', 'd', 'e'), array_values(iterator_to_array(clone $this->instance)));
	}

	/**
	 * Method to test toArray().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Utilities\Iterator\PriorityQueue::toArray
	 */
	public function testToArray()
	{
		$this->assertEquals($this->instance->toArray(), array_values(iterator_to_array(clone $this->instance)));
	}

	/**
	 * testBind
	 *
	 * @return  void
	 */
	public function testBind()
	{
		$queue = new PriorityQueue(array('a', 'b', 'c', 'd', 'e'));

		$this->assertEquals(array('a', 'b', 'c', 'd', 'e'), array_values(iterator_to_array(clone $queue)));
		$this->assertEquals($this->instance, $queue);
	}

	/**
	 * Method to test serialize().
	 *
	 * @return void
	 *
	 * @covers Windwalker\Core\Utilities\Iterator\PriorityQueue::serialize
	 * @covers Windwalker\Core\Utilities\Iterator\PriorityQueue::unserialize
	 */
	public function testSerialize()
	{
		$string = serialize(clone $this->instance);

		$queue = unserialize($string);

		$this->assertEquals($queue, $this->instance);
	}

	/**
	 * testMerge
	 *
	 * @return  void
	 */
	public function testMerge()
	{
		$queue = new PriorityQueue;

		$queue->insert('A', Priority::LOW);
		$queue->insert('B', Priority::LOW);
		$queue->insert('C', Priority::LOW);
		$queue->insert('D', Priority::LOW);
		$queue->insert('E', Priority::LOW);

		$this->instance->merge($queue);

		$this->assertEquals(
			array('A', 'B', 'C', 'D', 'E', 'a', 'b', 'c', 'd', 'e'),
			$this->instance->toArray()
		);
	}
}
