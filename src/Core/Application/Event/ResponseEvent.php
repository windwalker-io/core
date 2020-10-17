<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\Event;

use Windwalker\Event\AbstractEvent;

/**
 * The ResponseEvent class.
 */
class ResponseEvent extends AbstractEvent
{
    protected $response;
}
