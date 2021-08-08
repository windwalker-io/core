<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    MIT
 */

namespace Windwalker\Core\Security\Exception;

/**
 * The UnauthorizedException class.
 *
 * @since  3.2
 */
class UnauthorizedException extends \RuntimeException
{
    /**
     * UnauthorizedException constructor.
     *
     * @param string                $message  The Exception message to throw.
     * @param int                   $code     Use 401 or 403 to forbidden access.
     * @param \Exception|\Throwable $previous Previous exception object.
     */
    public function __construct($message = '', $code = 401, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
