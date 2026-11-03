<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use RuntimeException;
use Windwalker\Utilities\Exception\VerbosityExceptionInterface;

/**
 * Exception that reports a different message depending on the requested
 * verbosity, used to cover WebApplication::logError()'s
 * VerbosityExceptionInterface branch.
 */
class VerbosityAwareException extends RuntimeException implements VerbosityExceptionInterface
{
    public function getDebugMessage(): string
    {
        return $this->getMessage();
    }

    public function getMessageByVerbosity(int|bool $verbosity = false): string
    {
        return 'verbose: ' . $this->getMessage();
    }
}
