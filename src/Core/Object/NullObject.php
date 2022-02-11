<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Object;

/**
 * The NullObject class.
 *
 * @since  2.0
 */
class NullObject implements NullObjectInterface, SilencerArrayAccessInterface
{
    /**
     * Is this object not contain any values.
     *
     * @return boolean
     */
    public function isNull()
    {
        return true;
    }

    /**
     * Is this object not contain any values.
     *
     * @return  boolean
     */
    public function notNull()
    {
        return false;
    }

    /**
     * __get
     *
     * @param $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        return null;
    }

    /**
     * __set
     *
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        return;
    }

    /**
     * __isset
     *
     * @param $name
     *
     * @return  mixed
     */
    public function __isset($name)
    {
        return false;
    }

    /**
     * __toString
     *
     * @return  string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * __unset
     *
     * @param $name
     *
     * @return  mixed
     */
    public function __unset($name)
    {
        return;
    }

    /**
     * __call
     *
     * @param $name
     * @param $args
     *
     * @return  mixed
     */
    public function __call($name, $args)
    {
        return null;
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable An instance of an object implementing Iterator or Traversable
     */
    #[\ReturnTypeWillChange]
        #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator([]);
    }

    /**
     * Is a property exists or not.
     *
     * @param mixed $offset Offset key.
     *
     * @return  boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return false;
    }

    /**
     * Get a property.
     *
     * @param mixed $offset Offset key.
     *
     * @throws  \InvalidArgumentException
     * @return  mixed The value to return.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return null;
    }

    /**
     * Set a value to property.
     *
     * @param mixed $offset Offset key.
     * @param mixed $value  The value to set.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        return;
    }

    /**
     * Unset a property.
     *
     * @param mixed $offset Offset key to unset.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        return;
    }

    /**
     * Count this object.
     *
     * @return  int
     */
    #[\ReturnTypeWillChange]
        #[\ReturnTypeWillChange]
    public function count()
    {
        return 0;
    }
}
