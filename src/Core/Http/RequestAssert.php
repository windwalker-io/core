<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Security\Exception\UnauthorizedException;
use Windwalker\Http\Exception\HttpRequestException;
use Windwalker\Utilities\Assert\RuntimeAssert;

/**
 * The RequestAssert class.
 */
class RequestAssert extends RuntimeAssert
{
    /**
     * @template T
     *
     * @param  T              $assertion
     * @param  string         $message
     * @param  mixed|null     $value
     * @param  callable|null  $exception
     *
     * @return  T
     */
    public static function assertBadRequest(
        mixed $assertion,
        string $message = 'Bad Request',
        mixed $value = null,
        ?callable $exception = null
    ): mixed {
        $exception ??= static fn (string $message) => new HttpRequestException($message, 400);

        return static::assert($assertion, $message, $value, $exception);
    }

    /**
     * @template T
     *
     * @param  T              $assertion
     * @param  string         $message
     * @param  mixed|null     $value
     * @param  callable|null  $exception
     *
     * @return  T
     */
    public static function assertUnauthorized(
        mixed $assertion,
        string $message = 'Unauthorized',
        mixed $value = null,
        ?callable $exception = null
    ): mixed {
        $exception ??= static fn (string $message) => new UnauthorizedException($message, 401);

        return static::assert($assertion, $message, $value, $exception);
    }

    /**
     * @template T
     *
     * @param  T              $assertion
     * @param  string         $message
     * @param  mixed|null     $value
     * @param  callable|null  $exception
     *
     * @return  T
     */
    public static function assertForbidden(
        mixed $assertion,
        string $message = 'Forbidden',
        mixed $value = null,
        ?callable $exception = null
    ): mixed {
        $exception ??= static fn (string $message) => new UnauthorizedException($message, 403);

        return static::assert($assertion, $message, $value, $exception);
    }

    /**
     * @template T
     *
     * @param  T              $assertion
     * @param  string         $message
     * @param  mixed|null     $value
     * @param  callable|null  $exception
     *
     * @return  T
     */
    public static function assertNotFound(
        mixed $assertion,
        string $message = 'Not found',
        mixed $value = null,
        ?callable $exception = null
    ): mixed {
        $exception ??= fn (string $message) => new RouteNotFoundException($message, 404);

        return static::assert($assertion, $message, $value, $exception);
    }

    protected static function exception(): callable
    {
        return static fn(string $msg) => new HttpRequestException($msg);
    }
}
