<?php

declare(strict_types=1);

namespace Windwalker\Console\Input;

use Attribute;

/**
 * The InputArgument class.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class InputArgument extends \Symfony\Component\Console\Input\InputArgument
{
}
