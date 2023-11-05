<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Stringable;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;

/**
 * Interface WebApplicationInterface
 */
interface WebApplicationInterface extends ApplicationInterface
{
    /**
     * Redirect to another URL.
     *
     * @param  string|Stringable  $url
     * @param  int                $code
     * @param  bool               $instant
     *
     * @return ResponseInterface
     */
    public function redirect(string|Stringable $url, int $code = 303, bool $instant = false): ResponseInterface;

    /**
     * Emit this response.
     *
     * @param  ResponseInterface  $response
     *
     * @return  void
     */
    public function respond(ResponseInterface $response): void;

    /**
     * @param  mixed  $res
     *
     * @return ResponseInterface
     *
     * @throws \JsonException
     * @since  4.0
     */
    public static function anyToResponse(mixed $res): ResponseInterface;
}
