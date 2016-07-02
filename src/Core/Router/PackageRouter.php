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
class PackageRouter implements RouteBuilderInterface
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
	 * @throws \OutOfRangeException
	 */
	public function route($route, $queries = [], $type = CoreRouter::TYPE_PATH)
	{
		$queries = is_scalar($queries) ? ['id' => $queries] : $queries;
		
		try
		{
			if ($this->router->hasRoute($this->package->getName() . '@' . $route))
			{
				return $this->router->build($this->package->getName() . '@' . $route, $queries, $type);
			}

			return $this->router->build($route, $queries, $type);
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
	 * fullRoute
	 *
	 * @param string $route
	 * @param array  $queries
	 *
	 * @return  string
	 */
	public function fullRoute($route, $queries = [])
	{
		return $this->route($route, $queries, static::TYPE_FULL);
	}

	/**
	 * rawRoute
	 *
	 * @param string $route
	 * @param array  $queries
	 *
	 * @return  string
	 */
	public function rawRoute($route, $queries = [])
	{
		return $this->route($route, $queries, static::TYPE_RAW);
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
		$queries = is_scalar($queries) ? ['id' => $queries] : $queries;
		
		$queries = (array) $queries;
		$token = $this->package->container->get('security.csrf')->getFormToken();
		$queries[$token] = 1;

		return $this->route($route, $queries, $type);
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
	 * getMatched
	 *
	 * @return  \Windwalker\Router\Route
	 */
	public function getMatched()
	{
		return $this->router->getMatched();
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
