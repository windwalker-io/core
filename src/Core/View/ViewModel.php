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
    protected $nullRepository;

    /**
     * Property model.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Property models.
     *
     * @var Repository[]
     */
    protected $repositories;

    /**
     * Method to get property Model
     *
     * @param  string $name
     *
     * @return Repository
     */
    public function getRepository($name = null)
    {
        $name = strtolower($name);

        if ($name) {
            if (isset($this->repositories[$name])) {
                return $this->repositories[$name];
            }

            return $this->getNullRepository();
        }

        return $this->repository ?: $this->getNullRepository();
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
    public function setRepository(Repository $model, $default = null, $customName = null)
    {
        if ($default === true) {
            $this->repository = $model;
        }

        $name = $customName ?: $model->getName();
        $name = $name ?: uniqid();

        $this->repositories[strtolower($name)] = $model;

        return $this;
    }

    /**
     * get
     *
     * @param string $name
     * @param string $repoName
     * @param array  $args
     *
     * @return mixed
     */
    public function get($name, $repoName = null, ...$args)
    {
        $repo = $this->getRepository($repoName);

        if (!$repo) {
            return null;
        }

        $method = 'get' . ucfirst($name);

        if (!is_callable([$repo, $method])) {
            return null;
        }

        return $repo->$method(...$args);
    }

    /**
     * get
     *
     * @param string $name
     * @param string $repoName
     * @param array  $args
     *
     * @return mixed
     */
    public function load($name, $repoName = null, ...$args)
    {
        $repo = $this->getRepository($repoName);

        if (!$repo) {
            return null;
        }

        $method = 'load' . ucfirst($name);

        if (!is_callable([$repo, $method])) {
            return null;
        }

        return $repo->$method(...$args);
    }

    /**
     * removeModel
     *
     * @param string $name
     *
     * @return  static
     */
    public function removeRepository($name)
    {
        // If is default model, remove it.
        if ($this->repositories[$name] === $this->repository) {
            $this->repository = null;
        }

        unset($this->repositories[$name]);

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
        return isset($this->repositories[$name]);
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
        if (strtolower(substr($name, -5)) === 'model') {
            $method = substr($name, 0, -5) . 'Repository';

            return $this->$method(...$args);
        }

        $model = $this->getRepository();

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
        return $this->getRepository($offset);
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
        $this->removeRepository($offset);
    }

    /**
     * Count this object.
     *
     * @return  int
     */
    public function count()
    {
        return count($this->repositories);
    }

    /**
     * Method to get property NullModel
     *
     * @return  Repository
     */
    public function getNullRepository()
    {
        if (!$this->nullRepository) {
            $this->nullRepository = new Repository();

            $this->nullRepository['is.null'] = true;
            $this->nullRepository['null']    = true;

            return $this->nullRepository;
        }

        return $this->nullRepository;
    }

    /**
     * Method to set property nullModel
     *
     * @param   Repository $nullRepo
     *
     * @return  static  Return self to support chaining.
     */
    public function setNullRepository($nullRepo)
    {
        $this->nullRepository = $nullRepo;

        return $this;
    }
}
