<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Http\Helper\HeaderHelper;
use Windwalker\Http\Response\Response;

/**
 * The CorsHandler class.
 */
class CorsHandler
{
    protected ResponseInterface $response;

    /**
     * create
     *
     * @param  ResponseInterface|null  $response
     *
     * @return  static
     */
    public static function create(?ResponseInterface $response = null): static
    {
        return new static($response ?? new Response());
    }

    /**
     * CorsHandler constructor.
     *
     * @param  ResponseInterface|null  $response
     */
    public function __construct(?ResponseInterface $response = null)
    {
        $this->response = $response ?? new Response();
    }

    /**
     * allowOrigin
     *
     * @param string|array $domain
     * @param bool         $replace
     *
     * @return static
     */
    public function allowOrigin(string|array $domain = '*', bool $replace = false): static
    {
        $domain = implode(' ', (array) $domain);

        if ($replace) {
            $this->response = $this->response->withHeader('Access-Control-Allow-Origin', $domain);
        } else {
            $this->response = $this->response->withAddedHeader('Access-Control-Allow-Origin', $domain);
        }

        return $this;
    }

    /**
     * allowMethods
     *
     * @param string|array $methods
     *
     * @return  static
     */
    public function allowMethods(string|array $methods = '*'): static
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
    public function allowHeaders(string|array $headers = '*'): static
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
    public function maxAge(int $seconds): static
    {
        $this->response = $this->response->withHeader('Access-Control-Max-Age', (string) $seconds);

        return $this;
    }

    /**
     * allowCredentials
     *
     * @param bool $bool
     *
     * @return  static
     */
    public function allowCredentials(bool $bool = true): static
    {
        $boolText = $bool ? 'true' : 'false';

        $this->response = $this->response->withHeader('Access-Control-Allow-Credentials', $boolText);

        return $this;
    }

    /**
     * exposeHeaders
     *
     * @param string|array $headers
     *
     * @return  static
     */
    public function exposeHeaders(string|array $headers = '*'): static
    {
        $headers = array_map([HeaderHelper::class, 'normalizeHeaderName'], (array) $headers);
        $headers = implode(', ', $headers);

        $this->response = $this->response->withHeader('Access-Control-Allow-Headers', $headers);

        return $this;
    }

    /**
     * @template R
     *
     * @param  class-string<R>  $response
     *
     * @return  R
     */
    public function handle(ResponseInterface $response): ResponseInterface
    {
        $headers = $this->response->getHeaders();

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $response = $response->withAddedHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Method to get property Response
     *
     * @return  ResponseInterface
     */
    public function getResponse(): ResponseInterface
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
    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;

        return $this;
    }
}
