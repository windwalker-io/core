<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Cache\Cache;
use Windwalker\Cache\DataHandler\RawDataHandler;
use Windwalker\Cache\Storage\RuntimeStorage;
use Windwalker\Ioc;
use Windwalker\Registry\Registry;
use Windwalker\Router\Matcher\MatcherInterface;
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
	 * Property cache.
	 *
	 * @var  Cache
	 */
	protected $cache;

	/**
	 * Class init.
	 *
	 * @param array            $routes
	 * @param MatcherInterface $matcher
	 */
	public function __construct(array $routes, MatcherInterface $matcher)
	{
		parent::__construct($routes, $matcher);

		$this->cache = new Cache(new RuntimeStorage, new RawDataHandler);
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
		if (!array_key_exists($route, $this->routes))
		{
			throw new \OutOfRangeException('Route: ' . $route . ' not found.');
		}

		// Hook
		$extra = $this->routes[$route]->getExtra();

		if (isset($extra['hook']['build']))
		{
			if (!is_callable($extra['hook']['build']))
			{
				throw new \LogicException(sprintf('The build hook: "%s" of route: "%s" not found', implode('::', (array) $extra['hook']['build']), $route));
			}

			call_user_func($extra['hook']['build'], $this, $route, $queries, $type, $xhtml);
		}

		Ioc::getDispatcher()->triggerEvent('onRouterBeforeRouteBuild', array(
			'route'   => &$route,
			'queries' => &$queries,
			'type'    => &$type,
			'xhtml'   => &$xhtml,
			'router'  => $this
		));

		$key = $this->getCacheKey(array($route, $queries, $type, $xhtml));

		if ($this->cache->exists($key))
		{
			return $this->cache->get($key);
		}

		// Build
		$url = parent::build($route, $queries);

		Ioc::getDispatcher()->triggerEvent('onRouterAfterRouteBuild', array(
			'url'     => &$url,
			'route'   => &$route,
			'queries' => &$queries,
			'type'    => &$type,
			'xhtml'   => &$xhtml,
			'router'  => $this
		));

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

		$this->cache->set($key, $url);

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
	public function html($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
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
	public function http($route, $queries = array(), $type = RestfulRouter::TYPE_PATH)
	{
		return $this->build($route, $queries, $type, false);
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
		return $this->html($route, $queries, $type);
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
		return $this->http($route, $queries, $type);
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

		// Hooks
		if (isset($extra['hook']['match']))
		{
			if (!is_callable($extra['hook']['match']))
			{
				throw new \LogicException(sprintf('The match hook: "%s" of route: "%s" not found', implode('::', (array) $extra['hook']['match']), $route->getName()));
			}

			call_user_func($extra['hook']['match'], $this, $route, $method, $options);
		}

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
	public function fetchControllerSuffix($method = 'GET', $customSuffix = array())
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

	/**
	 * getCacheKey
	 *
	 * @param   mixed  $data
	 *
	 * @return  string
	 */
	protected function getCacheKey($data)
	{
		return md5(json_encode($data));
	}
}
