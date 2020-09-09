<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventAwareInterface;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface extends EventAwareInterface, DispatcherAwareInterface
{
}
