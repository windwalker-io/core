<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Http\HttpClient;
use Windwalker\Http\Transport\CurlTransport;
use Windwalker\Http\Transport\StreamTransport;
use Windwalker\Http\Transport\TransportInterface;

/**
 * The HttpRetriever class.
 *
 * @method  static  ResponseInterface  request($method, $url, $data = null, $headers = array())
 * @method  static  ResponseInterface  download($url, $dest, $data = null, $headers = array())
 * @method  static  ResponseInterface  send(RequestInterface $request)
 * @method  static  ResponseInterface  options($url, $headers = array())
 * @method  static  ResponseInterface  head($url, $headers = array())
 * @method  static  ResponseInterface  get($url, $data = null, $headers = array())
 * @method  static  ResponseInterface  post($url, $data, $headers = array())
 * @method  static  ResponseInterface  put($url, $data, $headers = array())
 * @method  static  ResponseInterface  delete($url, $data = null, $headers = array())
 * @method  static  ResponseInterface  trace($url, $headers = array())
 * @method  static  ResponseInterface  patch($url, $data, $headers = array())
 *
 * @since  {DEPLOY_VERSION}
 */
class HttpRetriever
{
	/**
	 * Property instance.
	 *
	 * @var  HttpClient
	 */
	protected static $instance;

	/**
	 * Property transport.
	 *
	 * @var  string
	 */
	protected static $transport = 'curl';

	/**
	 * Property options.
	 *
	 * @var  array
	 */
	protected static $options = [];

	/**
	 * __callStatic
	 *
	 * @param   string $name
	 * @param   array  $args
	 *
	 * @return  mixed
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic($name, $args)
	{
		$http = static::create();

		if (is_callable([$http, $name]))
		{
			return $http->$name(...$args);
		}

		throw new \BadMethodCallException(sprintf('Method: %s() not found in HttpClient', $name));
	}

	/**
	 * getInstance
	 *
	 * @param array                   $options
	 * @param TransportInterface|null $transport
	 *
	 * @return  HttpClient
	 */
	public static function getInstance($options = [], TransportInterface $transport = null)
	{
		if (!static::$instance)
		{
			static::$instance = static::create($options, $transport);
		}

		return static::$instance;
	}

	/**
	 * create
	 *
	 * @param array                   $options
	 * @param TransportInterface|null $transport
	 *
	 * @return  HttpClient
	 */
	public static function create($options = [], TransportInterface $transport = null)
	{
		$transport = $transport ? : static::getTransport();
		$options = $options ? : static::getOptions();

		switch ($transport)
		{
			case 'mock':
				$mockOptions = isset($options['mock']) ? $options['mock'] : [];

				$transport = new StreamTransport($mockOptions);
				break;
			case 'stream':
				$streamOptions = isset($options['stream']) ? $options['stream'] : [];

				$transport = new StreamTransport($streamOptions);
				break;

			case 'curl':
			default:
				$curlOptions = isset($options['curl']) ? $options['curl'] : [];

				$transport = new CurlTransport($curlOptions);
				break;
		}

		return new HttpClient($options, $transport);
	}

	/**
	 * Method to get property Transport
	 *
	 * @return  string
	 */
	public static function getTransport()
	{
		return static::$transport;
	}

	/**
	 * Method to set property transport
	 *
	 * @param   string|TransportInterface $transport
	 *
	 * @return  void
	 */
	public static function setTransport($transport)
	{
		static::$transport = $transport;
	}

	/**
	 * Method to get property Options
	 *
	 * @return  array
	 */
	public static function getOptions()
	{
		return static::$options;
	}

	/**
	 * Method to set property options
	 *
	 * @param   array $options
	 *
	 * @return  void
	 */
	public static function setOptions($options)
	{
		static::$options=$options;
	}
}
