<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
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
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';

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
	 * @param CoreRouter      $router
	 */
	public function __construct(AbstractPackage $package, CoreRouter $router)
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
	public function build($route, $queries = array(), $type = CoreRouter::TYPE_RAW, $xhtml = false)
	{
		try
		{
			// TODO: Simplify this process
			return $this->router->build($this->package->getName() . '@' . $route, $queries, $type, $xhtml);
		}
		catch (\OutOfRangeException $e)
		{
			try
			{
				return $this->router->build($route, $queries, $type, $xhtml);
			}
			catch (\OutOfRangeException $e)
			{
				$config = $this->package->getContainer()->get('system.config');

				if (!$xhtml || $config->get('routing.debug', false))
				{
					throw $e;
				}
				elseif ($config->get('system.debug', false))
				{
					return sprintf('javascript:alert(\'%s\')', $e->getMessage());
				}

				return '#';
			}
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
	public function buildHtml($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
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
	public function buildHttp($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
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
	public function html($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
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
	public function http($route, $queries = array(), $type = CoreRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, false);
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
	public function setPackage(AbstractPackage $package)
	{
		$this->package = $package;

		return $this;
	}
}
