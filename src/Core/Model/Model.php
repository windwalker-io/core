<?php
/**
 * Part of auth project. 
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Model;

use Windwalker\Cache\Cache;
use Windwalker\Cache\Storage\RuntimeStorage;
use Windwalker\Core\Ioc;
use Windwalker\Database\Driver\DatabaseAwareTrait;
use Windwalker\Database\Driver\DatabaseDriver;
use Windwalker\Model\AbstractModel;
use Windwalker\Model\DatabaseModelInterface;
use Windwalker\Registry\Registry;

/**
 * Class DatabaseModel
 *
 * @since 1.0
 */
class Model extends AbstractModel
{
	/**
	 * Property cache.
	 *
	 * @var  Cache
	 */
	protected $cache = null;

	/**
	 * Property config.
	 *
	 * @var  Registry
	 */
	protected $config = null;

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
	 * @param   Registry|array  $config The model config.
	 * @param   Registry        $state  The model state.
	 *
	 * @since   1.0
	 */
	public function __construct($config = null, Registry $state = null)
	{
		$this->setConfig($config);

		$this->resetCache();

		parent::__construct($state);

		$this->initialise();
	}

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
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
		$this->cache = new Cache(new RuntimeStorage);

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
