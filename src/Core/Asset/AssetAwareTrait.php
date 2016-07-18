<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

/**
 * The AssetAwareTrait class.
 *
 * @since  3.0
 */
trait AssetAwareTrait
{
	/**
	 * Property asset.
	 *
	 * @var  AssetManager
	 */
	protected $asset;

	/**
	 * Method to get property Asset
	 *
	 * @return  AssetManager
	 */
	public function getAsset()
	{
		return $this->asset;
	}

	/**
	 * Method to set property asset
	 *
	 * @param   AssetManager $asset
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setAsset($asset)
	{
		$this->asset = $asset;

		return $this;
	}
}
