<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

/**
 * The Script class.
 *
 * @since  3.0
 */
class ScriptManager
{
	use AssetAwareTrait;

	/**
	 * Property inited.
	 *
	 * @var  array
	 */
	protected static $inited = array();

	/**
	 * ScriptManager constructor.
	 *
	 * @param AssetManager $asset
	 */
	public function __construct(AssetManager $asset)
	{
		$this->asset = $asset;
	}

	/**
	 * inited
	 *
	 * @param   string $name
	 * @param   mixed  ...$data
	 *
	 * @return bool
	 */
	public function inited($name, ...$data)
	{
		$id = $this->getInitedId(...$data);

		if (!isset(static::$inited[$name][$id]))
		{
			static::$inited[$name][$id] = true;

			return false;
		}

		return true;
	}

	/**
	 * getInitedId
	 *
	 * @param   mixed  ...$data
	 *
	 * @return  string
	 */
	public function getInitedId(...$data)
	{
		return sha1(serialize($data));
	}
}
