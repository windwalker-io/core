<?php

declare(strict_types=1);

namespace Windwalker\Core\Router\Exception;

use RuntimeException;
use Throwable;

/**
 * The UnAllowedMethodException class.
 */
class UnAllowedMethodException extends RuntimeException
{
    /**
     * RouteNotFoundException constructor.
     *
     * @param  string      $message
     * @param  int         $code
     * @param  Throwable  $previous
     */
    public function __construct($message = 'Method not allowed', $code = 405, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
