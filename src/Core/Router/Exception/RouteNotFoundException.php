<?php

declare(strict_types=1);

namespace Windwalker\Core\Router\Exception;

use RuntimeException;
use Throwable;

/**
 * The RouteNotFoundException class.
 *
 * @since  2.0
 */
class RouteNotFoundException extends RuntimeException
{
    /**
     * RouteNotFoundException constructor.
     *
     * @param  string      $message
     * @param  int         $code
     * @param  ?Throwable  $previous
     */
    public function __construct($message = 'Not found', $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
