<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\User\Exception;

/**
 * The LoginFailException class.
 *
 * @since  3.5.2
 */
class LoginFailException extends AuthenticateFailException
{
    /**
     * Class init.
     *
     * @param string       $mainMessage
     * @param string|array $messages
     * @param int          $code
     * @param \Throwable   $previous
     */
    public function __construct(string $mainMessage, $messages = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($messages, $code, $previous);
        
        $this->message = $mainMessage;
    }
}
