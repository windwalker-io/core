<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\User\Exception;

use Windwalker\Core\Utilities\Exception\MultiMessagesExceptionTrait;

/**
 * The AccessDenyException class.
 *
 * @since  {DEPLOY_VERSION}
 */
class AccessDeniedException extends \RuntimeException
{
	use MultiMessagesExceptionTrait;
}
