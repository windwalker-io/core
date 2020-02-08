<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Assert;

use Windwalker\Http\Exception\HttpRequestException;
use Windwalker\Utilities\Assert\TypeAssert;

/**
 * The RequestAssert class.
 *
 * @since  __DEPLOY_VERSION__
 */
class RequestAssert extends TypeAssert
{
    /**
     * @var  string
     */
    protected static $exceptionClass = HttpRequestException::class;
}
