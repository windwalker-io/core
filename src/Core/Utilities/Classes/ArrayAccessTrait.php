<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The ArrayAccessTrait class.
 *
 * @since  {DEPLOY_VERSION}
 */
trait ArrayAccessTrait
{
	/**
	 * Property field.
	 *
	 * @var  string
	 */
	protected $dataField = 'data';
	
	/**
	 * Property is exist or not.
	 *
	 * @param mixed $offset Property key.
	 *
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		$field = $this->dataField;
		
		return isset($this->{$field}[$offset]);
	}

	/**
	 * Get a value of property.
	 *
	 * @param mixed $offset Property key.
	 *
	 * @return  mixed The value of this property.
	 */
	public function offsetGet($offset)
	{
		$field = $this->dataField;
		
		if (empty($this->{$field}[$offset]))
		{
			return null;
		}

		return $this->{$field}[$offset];
	}

	/**
	 * Set value to property
	 *
	 * @param mixed $offset Property key.
	 * @param mixed $value  Property value to set.
	 *
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$field = $this->dataField;
		
		if ($offset !== null)
		{
			$this->{$field}[$offset] = $value;
		}
		else
		{
			array_push($this->{$field}, $value);
		}
	}

	/**
	 * Unset a property.
	 *
	 * @param mixed $offset Key to unset.
	 *
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		$field = $this->dataField;
		
		unset($this->{$field}[$offset]);
	}
}
