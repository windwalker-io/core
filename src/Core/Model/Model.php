<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Model;

use Windwalker\Cache\Cache;
use Windwalker\Cache\DataHandler\RawDataHandler;
use Windwalker\Cache\Serializer\RawSerializer;
use Windwalker\Cache\Storage\ArrayStorage;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Registry\Registry;

/**
 * Class DatabaseModel
 *
 * @property-read  Registry  $config  Config object.
 *
 * @since 1.0
 */
class Model implements \ArrayAccess
{
	use BootableTrait;

	/**
	 * Make sure state at the first to easily debug.
	 *
	 * @var  Registry
	 */
	protected $state;

	/**
	 * Property cache.
	 *
	 * @var  Cache
	 */
	protected $cache;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name;

	/**
	 * Data source.
	 *
	 * @var  mixed
	 */
	protected $source;

	/**
	 * Property magicMethodPrefix.
	 *
	 * @var  array
	 */
	protected $magicMethodPrefix = array(
		'get',
		'load'
	);

	/**
	 * Instantiate the model.
	 *
	 * @param   Registry|array $config The model config.
	 * @param   Registry       $state  The model state.
	 * @param   mixed          $source The data source.
	 *
	 * @since   1.0
	 */
	public function __construct($config = null, Registry $state = null, $source = null)
	{
		$this->state  = ($state instanceof Registry) ? $state : new Registry;
		$this->source = $source;

		$this->setConfig($config);

		$this->resetCache();

		$this->bootTraits($this);

		$this->init();
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function init()
	{
		// Override if you need.
	}

	/**
	 * __call
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return  mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __call($name, $args = array())
	{
		$allow = false;

		foreach ($this->magicMethodPrefix as $prefix)
		{
			if (substr($name, 0, strlen($prefix)) == $prefix)
			{
				$allow = true;

				break;
			}
		}

		if (!$allow)
		{
			throw new \BadMethodCallException(sprintf("Method %s::%s not found.", get_called_class(), $name));
		}

		return null;
	}

	/**
	 * Method to get property Config
	 *
	 * @return  Registry
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Registry $config
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setConfig($config)
	{
		$this->config = $config instanceof Registry ? $config : new Registry($config);

		return $this;
	}

	/**
	 * Method to get property Name
	 *
	 * @return  string
	 */
	public function getName()
	{
		if (!$this->name)
		{
			if ($this->config['name'])
			{
				return $this->name = $this->config['name'];
			}

			$ref = new \ReflectionClass(get_called_class());

			$name = substr($ref->getShortName(), 0, -5);

			return $this->name = strtolower($name);
		}

		return $this->name;
	}

	/**
	 * Method to set property name
	 *
	 * @param   string $name
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * getStoredId
	 *
	 * @param string $id
	 *
	 * @return  string
	 */
	public function getCacheId($id = null)
	{
		$id = $id . json_encode($this->state->toArray());

		return sha1($id);
	}

	/**
	 * getCache
	 *
	 * @param string $id
	 *
	 * @return  mixed
	 */
	protected function getCache($id = null)
	{
		return $this->cache->get($this->getCacheId($id));
	}

	/**
	 * setCache
	 *
	 * @param string $id
	 * @param mixed  $item
	 *
	 * @return  mixed
	 */
	protected function setCache($id = null, $item = null)
	{
		$this->cache->set($this->getCacheId($id), $item);

		return $item;
	}

	/**
	 * hasCache
	 *
	 * @param string $id
	 *
	 * @return  bool
	 */
	protected function hasCache($id = null)
	{
		return $this->cache->exists($this->getCacheId($id));
	}

	/**
	 * resetCache
	 *
	 * @return  static
	 */
	public function resetCache()
	{
		$this->cache = new Cache(new ArrayStorage, new RawSerializer);

		return $this;
	}

	/**
	 * fetch
	 *
	 * @param string   $id
	 * @param callable $closure
	 *
	 * @return  mixed
	 */
	protected function fetch($id, $closure)
	{
		return $this->cache->call($this->getCacheId($id), $closure);
	}

	/**
	 * Get the model state.
	 *
	 * @return  Registry  The state object.
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set the model state.
	 *
	 * @param   Registry  $state  The state object.
	 *
	 * @return  void
	 */
	public function setState(Registry $state)
	{
		$this->state = $state;
	}

	/**
	 * get
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function get($key, $default = null)
	{
		return $this->state->get($key, $default);
	}

	/**
	 * set
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return  $this
	 */
	public function set($key, $value)
	{
		$this->state->set($key, $value);

		return $this;
	}

	/**
	 * reset
	 *
	 * @return  static
	 */
	public function reset()
	{
		$this->state->reset();

		return $this;
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
		return $this->state->exists($offset);
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
		return $this->state->get($offset);
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
		$this->state->set($offset, $value);
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
		$this->state->set($offset, null);
	}

	/**
	 * __get
	 *
	 * @param string $name
	 *
	 * @return  Registry
	 */
	public function __get($name)
	{
		if ($name == 'config')
		{
			return $this->config;
		}

		return null;
	}
}
