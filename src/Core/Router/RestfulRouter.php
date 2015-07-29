<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Registry\Registry;
use Windwalker\Router\Route;
use Windwalker\Router\Router;
use Windwalker\Utilities\ArrayHelper;

/**
 * The Router class.
 * 
 * @since  2.0
 */
class RestfulRouter extends Router
{
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';

	/**
	 * An array of HTTP Method => controller suffix pairs for routing the request.
	 *
	 * @var  array
	 */
	protected $suffixMap = array(
		'GET'     => 'GetController',
		'POST'    => 'SaveController',
		'PUT'     => 'SaveController',
		'PATCH'   => 'SaveController',
		'DELETE'  => 'DeleteController',
		'HEAD'    => 'HeadController',
		'OPTIONS' => 'OptionsController'
	);

	/**
	 * Property controller.
	 *
	 * @var  string
	 */
	protected $controller = null;

	/**
	 * Property uri.
	 *
	 * @var Registry
	 */
	protected $uri;

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
		$url = parent::build($route, $queries);
		$uri = $this->getUri();

		$script = $uri->get('script');
		$script = $script ? $script . '/' : null;

		if ($type == static::TYPE_PATH)
		{
			$url = $uri->get('base.path') . $script . ltrim($url, '/');
		}
		elseif ($type == static::TYPE_FULL)
		{
			$url = $uri->get('base.full') . $script . $url;
		}

		if ($xhtml)
		{
			$url = htmlspecialchars($url);
		}

		return $url;
	}

	/**
	 * buildHtml
	 *
	 * @param string  $route
	 * @param array   $queries
	 * @param string  $type
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
	 * @param string  $route
	 * @param array   $queries
	 * @param string  $type
	 *
	 * @return  string
	 */
	public function buildHttp($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, false);
	}

	/**
	 * match
	 *
	 * @param string $route
	 * @param string $method
	 * @param array  $options
	 *
	 * @throws  \UnexpectedValueException
	 * @return  Route
	 */
	public function match($route, $method = 'GET', $options = array())
	{
		$route = parent::match($route, $method, $options);

		$variables = $route->getVariables();
		$extra = $route->getExtra();

		$controller = ArrayHelper::getValue($extra, 'controller');

		if (!$controller)
		{
			throw new \UnexpectedValueException('Route profile should have "controller" element, the matched route: ' . $route->getName());
		}

		// Suffix
		$suffix = $this->fetchControllerSuffix($method, ArrayHelper::getValue($extra, 'action', array()));

		if ($suffix[0] != ':')
		{
			$suffix = '\\' . $suffix;
		}

		$controller = trim($controller, '\\') . $suffix;

		$extra['controller'] = $this->controller = $controller;

		$route->setExtra($extra);

		return $route;
	}

	/**
	 * Get the controller class suffix string.
	 *
	 * @param string $method
	 * @param array  $customSuffix
	 *
	 * @throws \RuntimeException
	 * @return  string
	 *
	 * @since   2.0
	 */
	protected function fetchControllerSuffix($method = 'GET', $customSuffix = array())
	{
		$method = strtoupper($method);

		// Validate that we have a map to handle the given HTTP method.
		if (!isset($this->suffixMap[$method]))
		{
			throw new \RuntimeException(sprintf('Unable to support the HTTP method `%s`.', $method), 404);
		}

		if (isset($customSuffix['*']))
		{
			return $customSuffix['*'];
		}

		$customSuffix = array_change_key_case($customSuffix, CASE_UPPER);

		$suffix = array_merge($this->suffixMap, $customSuffix);

		return trim($suffix[$method], '\\');
	}

	/**
	 * Set a controller class suffix for a given HTTP method.
	 *
	 * @param   string  $method  The HTTP method for which to set the class suffix.
	 * @param   string  $suffix  The class suffix to use when fetching the controller name for a given request.
	 *
	 * @return  static  Returns itself to support chaining.
	 */
	public function setHttpMethodSuffix($method, $suffix)
	{
		$this->suffixMap[strtoupper((string) $method)] = (string) $suffix;

		return $this;
	}

	/**
	 * Method to get property SuffixMap
	 *
	 * @return  array
	 */
	public function getSuffixMap()
	{
		return $this->suffixMap;
	}

	/**
	 * Method to set property suffixMap
	 *
	 * @param   array $suffixMap
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setSuffixMap($suffixMap)
	{
		$this->suffixMap = $suffixMap;

		return $this;
	}

	/**
	 * Method to get property Controller
	 *
	 * @return  string
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * Method to get property Uri
	 *
	 * @return  Registry
	 */
	public function getUri()
	{
		if (!$this->uri)
		{
			throw new \LogicException('No uri object set to Router.');
		}

		return $this->uri;
	}

	/**
	 * Method to set property uri
	 *
	 * @param   Registry $uri
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setUri(Registry $uri)
	{
		$this->uri = $uri;

		return $this;
	}
}
