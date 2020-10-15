<?php declare(strict_types=1);
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Router\Exception;

/**
 * The RouteNotFoundException class.
 *
 * @since  2.0
 */
class RouteNotFoundException extends \RuntimeException
{
    /**
     * RouteNotFoundException constructor.
     *
     * @param  string      $message
     * @param  int         $code
     * @param  ?\Throwable  $previous
     */
    public function __construct($message = 'Page not found', $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
