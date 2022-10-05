<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\DI\DICreateTrait;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The ForceSslMiddleware class.
 */
class ForceSslMiddleware implements MiddlewareInterface
{
    use DICreateTrait;

    /**
     * ForceSslMiddleware constructor.
     *
     * @param  bool        $enabled
     * @param  int         $redirectCode
     * @param  mixed|null  $headers
     */
    public function __construct(
        protected bool $enabled = true,
        protected int $redirectCode = 303,
        protected mixed $headers = null
    ) {
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param  ServerRequestInterface   $request
     * @param  RequestHandlerInterface  $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->enabled) {
            $uri = $request->getUri();

            if ($uri->getScheme() === 'http') {
                $uri = $uri->withScheme('https');

                $res = new RedirectResponse(
                    $uri,
                    $this->normalizeCode($this->redirectCode)
                );

                $headers = $this->headers;

                if (is_array($headers)) {
                    foreach ($headers as $name => $header) {
                        $res = $res->withAddedHeader($name, $header);
                    }
                } elseif (is_callable($headers)) {
                    $res = $headers($res, $request);
                }

                return $res;
            }
        }

        return $handler->handle($request);
    }

    /**
     * normalizeCode
     *
     * @param  int  $code
     *
     * @return  int
     *
     * @since  3.5.23.2
     */
    protected function normalizeCode(int $code): int
    {
        if ($code < 300 || $code >= 400) {
            $code = 303;
        }

        return $code;
    }

    /**
     * @param  bool  $enabled
     *
     * @return  static  Return self to support chaining.
     */
    public function enabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param  int  $redirectCode
     *
     * @return  static  Return self to support chaining.
     */
    public function redirectCode(int $redirectCode): static
    {
        $this->redirectCode = $redirectCode;

        return $this;
    }

    /**
     * @param  mixed  $headers
     *
     * @return  static  Return self to support chaining.
     */
    public function headers(callable|array|null $headers): static
    {
        $this->headers = $headers;

        return $this;
    }
}
