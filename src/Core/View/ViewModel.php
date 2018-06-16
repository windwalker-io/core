<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\View;

use Windwalker\Core\Repository\Repository;

/**
 * The ViewModel class.
 *
 * @since  2.0
 */
class ViewModel implements \ArrayAccess
{
    /**
     * Property nullModel.
     *
     * @var Repository
     */
    protected $nullModel;

    /**
     * Property model.
     *
     * @var Repository
     */
    protected $model;

    /**
     * Property models.
     *
     * @var Repository[]
     */
    protected $models;

    /**
     * Method to get property Model
     *
     * @param  string $name
     *
     * @return Repository
     */
    public function getModel($name = null)
    {
        $name = strtolower($name);

        if ($name) {
            if (isset($this->models[$name])) {
                return $this->models[$name];
            }

            return $this->getNullModel();
        }

        return $this->model ?: $this->getNullModel();
    }

    /**
     * Method to set property model
     *
     * @param   Repository $model
     * @param   bool       $default
     * @param   string     $customName
     *
     * @return static Return self to support chaining.
     * @throws \ReflectionException
     */
    public function setModel(Repository $model, $default = null, $customName = null)
    {
        if ($default === true) {
            $this->model = $model;
        }

        $name = $customName ?: $model->getName();
        $name = $name ?: uniqid();

        $this->models[strtolower($name)] = $model;

        return $this;
    }

    /**
     * get
     *
     * @param string $name
     * @param string $modelName
     * @param array  $args
     *
     * @return mixed
     */
    public function get($name, $modelName = null, ...$args)
    {
        $model = $this->getModel($modelName);

        if (!$model) {
            return null;
        }

        $method = 'get' . ucfirst($name);

        if (!is_callable([$model, $method])) {
            return null;
        }

        return $model->$method(...$args);
    }

    /**
     * get
     *
     * @param string $name
     * @param string $modelName
     * @param array  $args
     *
     * @return mixed
     */
    public function load($name, $modelName = null, ...$args)
    {
        $model = $this->getModel($modelName);

        if (!$model) {
            return null;
        }

        $method = 'load' . ucfirst($name);

        if (!is_callable([$model, $method])) {
            return null;
        }

        return $model->$method(...$args);
    }

    /**
     * removeModel
     *
     * @param string $name
     *
     * @return  static
     */
    public function removeModel($name)
    {
        // If is default model, remove it.
        if ($this->models[$name] === $this->model) {
            $this->model = null;
        }

        unset($this->models[$name]);

        return $this;
    }

    /**
     * Method to check a model exists.
     *
     * @param string $name The model name to check.
     *
     * @return  boolean True if exists.
     */
    public function exists($name)
    {
        return isset($this->models[$name]);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array  $args
     *
     * @return  mixed
     */
    public function __call($name, $args)
    {
        $model = $this->getModel();

        return $model->$name(...$args);
    }

    /**
     * Is a property exists or not.
     *
     * @param mixed $offset Offset key.
     *
     * @return  boolean
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Get a property.
     *
     * @param mixed $offset Offset key.
     *
     * @throws  \InvalidArgumentException
     * @return  mixed The value to return.
     */
    public function offsetGet($offset)
    {
        return $this->getModel($offset);
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
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Use setModel() instead array access.');
    }

    /**
     * Unset a property.
     *
     * @param mixed $offset Offset key to unset.
     *
     * @throws  \InvalidArgumentException
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->removeModel($offset);
    }

    /**
     * Count this object.
     *
     * @return  int
     */
    public function count()
    {
        return count($this->models);
    }

    /**
     * Method to get property NullModel
     *
     * @return  Repository
     */
    public function getNullModel()
    {
        if (!$this->nullModel) {
            $this->nullModel = new Repository();

            $this->nullModel['is.null'] = true;
            $this->nullModel['null']    = true;

            return $this->nullModel;
        }

        return $this->nullModel;
    }

    /**
     * Method to set property nullModel
     *
     * @param   Repository $nullModel
     *
     * @return  static  Return self to support chaining.
     */
    public function setNullModel($nullModel)
    {
        $this->nullModel = $nullModel;

        return $this;
    }
}
