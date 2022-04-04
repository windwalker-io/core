<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

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
    public static function assertBadRequest(
        mixed $assertion,
        string $message = 'Bad Request',
        mixed $value = null,
        ?callable $exception = null
    ): void {
        $exception ??= static fn (string $message) => new HttpRequestException($message, 400);

        static::assert($assertion, $message, $value, $exception);
    }

    public static function assertUnauthorized(
        mixed $assertion,
        string $message = 'Unauthorized',
        mixed $value = null,
        ?callable $exception = null
    ): void {
        $exception ??= static fn (string $message) => new UnauthorizedException($message, 401);

        static::assert($assertion, $message, $value, $exception);
    }

    public static function assertForbidden(
        mixed $assertion,
        string $message = 'Forbidden',
        mixed $value = null,
        ?callable $exception = null
    ): void {
        $exception ??= static fn (string $message) => new UnauthorizedException($message, 403);

        static::assert($assertion, $message, $value, $exception);
    }


    public static function assertNotFound(
        mixed $assertion,
        string $message = 'Not found',
        mixed $value = null,
        ?callable $exception = null
    ): void {
        $exception ??= fn (string $message) => new RouteNotFoundException($message, 404);

        static::assert($assertion, $message, $value, $exception);
    }

    protected static function exception(): callable
    {
        return static fn(string $msg) => new HttpRequestException($msg);
    }
}
