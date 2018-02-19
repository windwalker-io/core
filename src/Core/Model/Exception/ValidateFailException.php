<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Model\Exception;

use Windwalker\Core\Utilities\Exception\MultiMessagesExceptionTrait;

/**
 * Class ValidateFailException
 *
 * @since  2.0
 */
class ValidateFailException extends \RuntimeException
{
    use MultiMessagesExceptionTrait;
}
