<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http\Exception;

use JetBrains\PhpStorm\NoReturn;

/**
 * Trait ApiErrorCodeTrait
 */
trait ApiErrorCodeTrait
{
    public function exception(?string $message = null): ApiException
    {
        return ApiException::fromEnum($this, $message);
    }

    public function throw(?string $message = null): never
    {
        throw $this->exception($message);
    }
}
