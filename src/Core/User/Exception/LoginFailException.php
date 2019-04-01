<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\User\Exception;

/**
 * The LoginFailException class.
 *
 * @since  __DEPLOY_VERSION__
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
