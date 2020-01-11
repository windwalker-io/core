<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\DateTime;

/**
 * The ChronosImmutable class.
 *
 * @since  3.5
 */
class ChronosImmutable extends \DateTimeImmutable implements ChronosInterface
{
    use DateTimeTrait;
}
