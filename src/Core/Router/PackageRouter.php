<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Package\AbstractPackage;

/**
 * The PackageRouter class, it is a decoration pattern to wrap package and router object.
 * 
 * @since  2.0
 */
class PackageRouter
{
	/**
	 * Property package.
	 *
	 * @var AbstractPackage
	 */
	protected $package;

	/**
	 * Property router.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Class init.
	 *
	 * @param AbstractPackage $package
	 * @param RestfulRouter   $router
	 */
	public function __construct(AbstractPackage $package, RestfulRouter $router)
	{
		$this->package = $package;
		$this->router  = $router;
	}

	/**
	 * build
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 * @param bool   $xhtml
	 *
	 * @return  string
	 */
	public function build($route, $queries = array(), $type = RestfulRouter::TYPE_RAW, $xhtml = false)
	{
		try
		{
			return $this->router->build($this->package->getName() . ':' . $route, $queries, $type, $xhtml);
		}
		catch (\OutOfRangeException $e)
		{
			return $this->router->build($route, $queries, $type, $xhtml);
		}
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
	public function buildHtml($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, true);
	}

	/**
	 * buildHttp
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return  string
	 */
	public function buildHttp($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, false);
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
	public function html($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, true);
	}

	/**
	 * buildHttp
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return  string
	 */
	public function http($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, false);
	}

	/**
	 * Method to get property Router
	 *
	 * @return  RestfulRouter
	 */
	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * Method to set property router
	 *
	 * @param   RestfulRouter $router
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRouter(RestfulRouter $router)
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
	public function setPackage(AbstractPackage $package)
	{
		$this->package = $package;

		return $this;
	}
}
