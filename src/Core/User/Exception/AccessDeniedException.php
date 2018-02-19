<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\User\Exception;

use Windwalker\Core\Utilities\Exception\MultiMessagesExceptionTrait;

/**
 * The AccessDenyException class.
 *
 * @since  3.0
 */
class AccessDeniedException extends \RuntimeException
{
    use MultiMessagesExceptionTrait;
}
