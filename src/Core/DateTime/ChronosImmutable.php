<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\DateTime;

/**
 * The ChronosImmutable class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ChronosImmutable extends \DateTimeImmutable implements ChronosInterface
{
    use DateTimeTrait;
}
