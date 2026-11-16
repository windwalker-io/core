<?php

declare(strict_types=1);

namespace Windwalker\Core\Form\Exception;

use RuntimeException;
use Windwalker\Utilities\Exception\MultiMessagesExceptionTrait;
use Windwalker\Utilities\Exception\SafeMessageExceptionInterface;
use Windwalker\Utilities\Exception\VerbosityExceptionInterface;

/**
 * The ValidateFailException class.
 */
class ValidateFailException extends RuntimeException implements VerbosityExceptionInterface, SafeMessageExceptionInterface
{
    use MultiMessagesExceptionTrait;

    protected string $debugMessage = '';

    /**
     * The validation message is always shown, even at the lowest verbosity.
     * Extra debug info is appended only in verbose mode.
     */
    public function getDebugMessage(): string
    {
        if ($this->debugMessage === '') {
            return $this->getMessage();
        }

        return $this->getMessage() . ' - ' . $this->debugMessage;
    }

    public function setDebugMessage(string $debugMessage): static
    {
        $this->debugMessage = $debugMessage;

        return $this;
    }

    public function getSafeMessage(): string
    {
        return $this->getMessage();
    }

    public function getMessageByVerbosity(int|bool $verbosity = false): string
    {
        return $verbosity ? $this->getDebugMessage() : $this->getMessage();
    }
}
