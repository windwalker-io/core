<?php

declare(strict_types=1);

namespace Windwalker\Core\Security\Exception;

use Exception;
use RuntimeException;
use Throwable;

/**
 * The UnauthorizedException class.
 *
 * @since  3.2
 */
class UnauthorizedException extends RuntimeException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param  string                $message   The Exception message to throw.
     * @param  int                   $code      Use 401 or 403 to forbidden access.
     * @param  Exception|Throwable  $previous  Previous exception object.
     */
    public function __construct($message = '', $code = 401, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
