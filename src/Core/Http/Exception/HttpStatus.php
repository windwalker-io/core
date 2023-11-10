<?php

declare(strict_types=1);

namespace Windwalker\Core\Http\Exception;

/**
 * The HttpStatus class.
 */
#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class HttpStatus
{
    public function __construct(public int $status)
    {
        //
    }
}
