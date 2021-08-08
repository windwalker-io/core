<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console\Input;

/**
 * The InputArgument class.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class InputArgument extends \Symfony\Component\Console\Input\InputArgument
{
}
