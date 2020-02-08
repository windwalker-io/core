<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Assert;

use Windwalker\Http\Exception\HttpRequestException;
use Windwalker\Utilities\Assert\TypeAssert;

/**
 * The RequestAssert class.
 *
 * @since  3.5.17
 */
class RequestAssert extends TypeAssert
{
    /**
     * @var  string
     */
    protected static $exceptionClass = HttpRequestException::class;
}
