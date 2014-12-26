<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Package\AbstractPackage;

/**
 * The PackageRouter class.
 * 
 * @since  {DEPLOY_VERSION}
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
	 * @param int    $type
	 * @param bool   $xhtml
	 *
	 * @return  string
	 */
	public function build($route, $queries = array(), $type = Router::TYPE_RAW, $xhtml = false)
	{
		if (count(explode(':', $route, 2)) < 2)
		{
			$route = $this->package->getName() .':' . $route;
		}

		return $this->router->build($route, $queries, $type, $xhtml);
	}

	/**
	 * buildHtml
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param int    $type
	 *
	 * @return  string
	 */
	public function buildHtml($route, $queries = array(), $type = Router::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, true);
	}

	/**
	 * buildHttp
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param int    $type
	 *
	 * @return  string
	 */
	public function buildHttp($route, $queries = array(), $type = Router::TYPE_PATH)
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
