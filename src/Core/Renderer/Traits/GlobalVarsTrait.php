<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Renderer\Traits;

/**
 * The GlobalVarsTrait class.
 *
 * @since  3.0
 */
trait GlobalVarsTrait
{
	/**
	 * Property globals.
	 *
	 * @var  array
	 */
	protected $globals = [];

	/**
	 * addGlobal
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 *
	 * @return  static
	 */
	public function addGlobal($name, $value)
	{
		$this->globals[$name] = $value;

		return $this;
	}

	/**
	 * removeGlobal
	 *
	 * @param   string  $name
	 *
	 * @return  static
	 */
	public function removeGlobal($name)
	{
		unset($this->globals[$name]);

		return $this;
	}

	/**
	 * Method to get property Globals
	 *
	 * @return  array
	 */
	public function getGlobals()
	{
		return $this->globals;
	}

	/**
	 * Method to set property globals
	 *
	 * @param   array $globals
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setGlobals($globals)
	{
		$this->globals = $globals;

		return $this;
	}
}
