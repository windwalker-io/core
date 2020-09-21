<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Windwalker\Http\Request\ServerRequest;

/**
 * The Request class.
 */
class Request extends ServerRequest
{
    protected array $params = [];

    protected string $task;
}
