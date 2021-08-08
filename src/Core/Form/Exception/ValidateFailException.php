<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Form\Exception;

use Windwalker\Utilities\Exception\MultiMessagesExceptionTrait;

/**
 * The ValidateFailException class.
 */
class ValidateFailException extends \RuntimeException
{
    use MultiMessagesExceptionTrait;
}
