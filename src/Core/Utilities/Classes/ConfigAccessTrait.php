<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The OptionAccessTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait ConfigAccessTrait
{
	/**
	 * Property options.
	 *
	 * @var  array
	 */
	protected $config = [];

	/**
	 * Method to get property Options
	 *
	 * @return  int
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Method to set property options
	 *
	 * @param   int $config
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setConfig($config)
	{
		$this->config = $config;

		return $this;
	}
}
