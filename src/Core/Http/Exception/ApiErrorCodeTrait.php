<?php

declare(strict_types=1);

namespace Windwalker\Core\Http\Exception;

use JetBrains\PhpStorm\NoReturn;

/**
 * Trait ApiErrorCodeTrait
 */
trait ApiErrorCodeTrait
{
    public function exception(?string $message = null, ?\Throwable $previous = null): ApiException
    {
        return ApiException::fromEnum($this, $message, $previous);
    }

    public function throw(?string $message = null, ?\Throwable $previous = null): never
    {
        throw $this->exception($message, $previous);
    }
}
