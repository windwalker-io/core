<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Router;

use Windwalker\Router\Router;
use Windwalker\Utilities\ArrayHelper;

/**
 * The Router class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class RestfulRouter extends Router
{
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
	 * match
	 *
	 * @param string $route
	 * @param string $method
	 * @param array  $options
	 *
	 * @throws  \UnexpectedValueException
	 * @return  array|bool
	 */
	public function match($route, $method = 'GET', $options = array())
	{
		$variables = parent::match($route, $method, $options);

		$controller = ArrayHelper::getValue($variables, '_controller');

		if (!$controller)
		{
			throw new \UnexpectedValueException('Route profile should have "_controller" element');
		}

		$variables['_action'] = ArrayHelper::getValue($variables, '_action', array());

		// Suffix
		$suffix = $this->fetchControllerSuffix($method, $variables['_action']);

		if ($suffix[0] != ':')
		{
			$suffix = '\\' . $suffix;
		}

		$controller = trim($controller, '\\') . $suffix;

		$variables['_controller'] = $this->controller = $controller;

		return $variables;
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
	 * @since   {DEPLOY_VERSION}
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
}
