<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\NullPackage;

/**
 * The PackageRouter class, it is a decoration pattern to wrap package and router object.
 * 
 * @since  2.0
 */
class CoreRoute
{
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';

	/**
	 * Property package.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * Property router.
	 *
	 * @var CoreRouter
	 */
	protected $router;

	/**
	 * Class init.
	 *
	 * @param CoreRouter      $router
	 * @param AbstractPackage $package
	 */
	public function __construct(CoreRouter $router, $package = null)
	{
		$this->router = $router;

		$this->setPackage($package);
	}

	/**
	 * build
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return string
	 */
	public function get($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
	{
		if ($this->router->hasRoute($this->package->getName() . '@' . $route))
		{
			return $this->router->build($this->package->getName() . '@' . $route, $queries, $type);
		}

		return $this->router->build($route, $queries, $type);
	}

	/**
	 * secure
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return  string
	 */
	public function secure($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
	{
		$queries = (array) $queries;
		$token = $this->package->container->get('security.csrf')->getFormToken();
		$queries[$token] = 1;

		return $this->get($route, $queries, $type);
	}

	/**
	 * buildHtml
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return  string
	 */
	public function encode($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
	{
		try
		{
			return htmlspecialchars($this->get($route, $queries, $type));
		}
		catch (\OutOfRangeException $e)
		{
			if ($this->package->app->get('routing.debug', false))
			{
				throw new \OutOfRangeException($e->getMessage(), $e->getCode(), $e);
			}
			elseif ($this->package->app->get('system.debug', false))
			{
				return sprintf('javascript:alert(\'%s\')', $e->getMessage());
			}

			return '#';
		}
	}

	/**
	 * escape
	 *
	 * @param   string  $text
	 *
	 * @return  string
	 */
	public function escape($text)
	{
		return htmlspecialchars($text);
	}

	/**
	 * Method to get property Router
	 *
	 * @return  CoreRouter
	 */
	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * Method to set property router
	 *
	 * @param   CoreRouter $router
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRouter(CoreRouter $router)
	{
		$this->router = $router;

		return $this;
	}

	/**
	 * Method to get property Package
	 *
	 * @return  AbstractPackage
	 */
	public function getPackage()
	{
		return $this->package;
	}

	/**
	 * Method to set property package
	 *
	 * @param   AbstractPackage $package
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setPackage(AbstractPackage $package = null)
	{
		if ($package === null)
		{
			$package = new NullPackage;
		}

		$this->package = $package;

		return $this;
	}
}
