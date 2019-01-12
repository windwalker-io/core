<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\DateTime;

use Windwalker\Core\Ioc;
use Windwalker\Database\Driver\AbstractDatabaseDriver;

/**
 * The DateTime class.
 *
 * @since  3.2
 */
class Chronos extends \DateTime implements ChronosInterface
{
    use DateTimeTrait;
}
