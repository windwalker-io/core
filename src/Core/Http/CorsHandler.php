<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Http\Helper\HeaderHelper;

/**
 * The CorsHandler to modify headers in Response which follows MDN.
 *
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 *
 * @since  3.1
 */
class CorsHandler
{
	/**
	 * Property response.
	 *
	 * @var  ResponseInterface
	 */
	protected $response;

	/**
	 * create
	 *
	 * @param ResponseInterface $response
	 *
	 * @return  static
	 */
	public static function create(ResponseInterface $response)
	{
		return new static($response);
	}

	/**
	 * CorsHandler constructor.
	 *
	 * @param ResponseInterface $response
	 */
	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
	}

	/**
	 * allowOrigin
	 *
	 * @param string|array $domain
	 *
	 * @return  static
	 */
	public function allowOrigin($domain = '*')
	{
		$domain = implode(' ', (array) $domain);

		$this->response = $this->response->withHeader('Access-Control-Allow-Origin', $domain);

		return $this;
	}

	/**
	 * allowMethods
	 *
	 * @param string|array $methods
	 *
	 * @return  static
	 */
	public function allowMethods($methods = '*')
	{
		$methods = array_map('strtoupper', (array) $methods);
		$methods = implode(', ', $methods);

		$this->response = $this->response->withHeader('Access-Control-Allow-Methods', $methods);

		return $this;
	}

	/**
	 * allowHeaders
	 *
	 * @param array|string $headers
	 *
	 * @return  static
	 */
	public function allowHeaders($headers = '*')
	{
		$headers = array_map([HeaderHelper::class, 'normalizeHeaderName'], (array) $headers);
		$headers = implode(', ', $headers);

		$this->response = $this->response->withHeader('Access-Control-Allow-Headers', $headers);

		return $this;
	}

	/**
	 * maxAge
	 *
	 * @param int $seconds
	 *
	 * @return  static
	 */
	public function maxAge($seconds)
	{
		$this->response = $this->response->withHeader('Access-Control-Max-Age', (int) $seconds);

		return $this;
	}

	/**
	 * allowCredentials
	 *
	 * @param bool $bool
	 *
	 * @return  static
	 */
	public function allowCredentials($bool = true)
	{
		$bool = $bool ? 'true' : 'false';

		$this->response = $this->response->withHeader('Access-Control-Allow-Credentials', $bool);

		return $this;
	}

	/**
	 * exposeHeaders
	 *
	 * @param string|array $headers
	 *
	 * @return  static
	 */
	public function exposeHeaders($headers = '*')
	{
		$headers = array_map([HeaderHelper::class, 'normalizeHeaderName'], (array) $headers);
		$headers = implode(', ', $headers);

		$this->response = $this->response->withHeader('Access-Control-Allow-Headers', $headers);

		return $this;
	}

	/**
	 * Method to get property Response
	 *
	 * @return  ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Method to set property response
	 *
	 * @param   ResponseInterface $response
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setResponse($response)
	{
		$this->response = $response;

		return $this;
	}
}
