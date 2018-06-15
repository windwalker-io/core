<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Controller\Traits;

use Windwalker\Core\Http\CorsHandler;

/**
 * The CorsTrait class.
 *
 * @since  3.2
 */
trait CorsTrait
{
    /**
     * Property handler.
     *
     * @var  CorsHandler
     */
    protected $corsHandler;

    /**
     * bootCorsTrait
     *
     * @return  void
     */
    public function bootCorsTrait()
    {
        $this->corsHandler = new CorsHandler();
    }

    /**
     * allowOrigin
     *
     * @param string|array $domain
     * @param bool         $replace
     *
     * @return static
     */
    public function allowOrigin($domain = '*', $replace = false)
    {
        $this->response = $this->corsHandler->setResponse($this->response)
            ->allowOrigin($domain, $replace)
            ->getResponse();

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
        $this->response = $this->corsHandler->setResponse($this->response)
            ->allowMethods($methods)
            ->getResponse();

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
        $this->response = $this->corsHandler->setResponse($this->response)
            ->allowHeaders($headers)
            ->getResponse();

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
        $this->response = $this->corsHandler->setResponse($this->response)
            ->maxAge($seconds)
            ->getResponse();

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
        $this->response = $this->corsHandler->setResponse($this->response)
            ->allowCredentials($bool)
            ->getResponse();

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
        $this->response = $this->corsHandler->setResponse($this->response)
            ->exposeHeaders($headers)
            ->getResponse();

        return $this;
    }
}
