<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\View\Helper\Set;

use Windwalker\Core\Mvc\MvcHelper;
use Windwalker\Core\View\AbstractView;
use Windwalker\Core\View\Helper\AbstractHelper;

/**
 * Class HelperSet
 *
 * @since  2.1
 */
class HelperSet implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Property helpers.
     *
     * @var  array
     */
    protected $helpers = [];

    /**
     * Property view.
     *
     * @var  AbstractView
     */
    protected $view;

    /**
     * Property namespaces.
     *
     * @var  array
     */
    protected static $namespaces = [];

    /**
     * HelperSet constructor.
     *
     * @param AbstractView $view
     */
    public function __construct(AbstractView $view)
    {
        $this->view = $view;
    }

    /**
     * __get
     *
     * @param string $name
     *
     * @return \Windwalker\Core\View\Helper\AbstractHelper
     */
    public function __get($name)
    {
        return $this->getHelper($name);
    }

    /**
     * __isset
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return (bool) $this[$key];
    }

    /**
     * __set
     *
     * @param   string         $name
     * @param   AbstractHelper $value
     *
     * @return  void
     */
    public function __set($name, $value)
    {
        $this->addHelper($name, $value);
    }

    /**
     * findHelper
     *
     * @param   string $name
     *
     * @return  bool|string
     */
    protected function findHelper($name)
    {
        $name = ucfirst($name);

        $package = $this->view->getPackage();

        $namespace = MvcHelper::getPackageNamespace($package, 1);

        $class = sprintf($namespace . '\Helper\%sHelper', $name);

        if (!class_exists($class)) {
            foreach (static::$namespaces as $ns) {
                $class = sprintf($ns . '\%sHelper', $name);

                if (class_exists($class)) {
                    break;
                }
            }
        }

        if (!class_exists($class)) {
            $class = 'Windwalker\Core\View\Helper\\' . ucfirst($name) . 'Helper';
        }

        if (!class_exists($class)) {
            return false;
        }

        return $class;
    }

    /**
     * addNamespace
     *
     * @param   string $namespace
     *
     * @return  void
     */
    public static function addNamespace($namespace)
    {
        static::$namespaces[] = $namespace;
    }

    /**
     * setNamespaces
     *
     * @param   array $namespaces
     *
     * @return  void
     */
    public static function setNamespaces(array $namespaces)
    {
        static::$namespaces = $namespaces;
    }

    /**
     * addHelper
     *
     * @param   string $name
     * @param   object $helper
     *
     * @return  static
     */
    public function addHelper($name, $helper)
    {
        $this->helpers[$name] = $helper;

        return $this;
    }

    /**
     * getHelper
     *
     * @param   string $name
     *
     * @return  AbstractHelper
     */
    public function getHelper($name)
    {
        if (empty($this->helpers[$name])) {
            $class = $this->findHelper($name);

            if ($class === false) {
                throw new \DomainException(sprintf('Helper: %s not found.', $name));
            }

            $this->helpers[$name] = new $class($this);
        }

        return $this->helpers[$name];
    }

    /**
     * Method to get property View
     *
     * @return  AbstractView
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Method to set property view
     *
     * @param   AbstractView $view
     *
     * @return  static  Return self to support chaining.
     */
    public function setView(AbstractView $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable An instance of an object implementing Iterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->helpers);
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset An offset to check for.
     *
     * @return boolean true on success or false on failure.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->helpers[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getHelper($offset);
    }

    /**
     * Offset to set
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->addHelper($offset, $value);
    }

    /**
     * Offset to unset
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (isset($this->helpers[$offset])) {
            unset($this->helpers[$offset]);
        }
    }

    /**
     * Count elements of an object
     *
     * @return int The custom count as an integer.
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->helpers);
    }
}
