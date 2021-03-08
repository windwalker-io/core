<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Application as SymfonyConsole;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;

/**
 * The ConsoleApplication class.
 */
class ConsoleApplication extends SymfonyConsole implements ApplicationInterface
{
    use ApplicationTrait;

    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function close(mixed $return = 0): void
    {
        exit((int) $return);
    }
}
