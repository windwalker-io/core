<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Object;

/**
 * The SilencerInterface class.
 * 
 * @since  {DEPLOY_VERSION}
 */
interface SilencerInterface
{
	/**
	 * __get
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __get($name);

	/**
	 * __set
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return mixed
	 */
	public function __set($name, $value);

	/**
	 * __isset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __isset($name);

	/**
	 * __toString
	 *
	 * @return  mixed
	 */
	public function __toString();

	/**
	 * __unset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __unset($name);

	/**
	 * __call
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return  mixed
	 */
	public function __call($name, $args);
}
