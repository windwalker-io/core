<?php

declare(strict_types=1);

namespace Windwalker\Core\Form\Exception;

use RuntimeException;
use Windwalker\Utilities\Exception\MultiMessagesExceptionTrait;

/**
 * The ValidateFailException class.
 */
class ValidateFailException extends RuntimeException
{
    use MultiMessagesExceptionTrait;
}
