<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\IO;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IOInterface;

/**
 * The IOSocketTrait class.
 */
trait IOSocketTrait
{
    protected InputInterface|OutputInterface|IOInterface|null $io = null;

    public function setIO(InputInterface|OutputInterface|IOInterface|null $io): void
    {
        $this->io = $io;
    }

    public function useIO(callable $callback): void
    {
        if ($this->io) {
            $callback($this->io);
        }
    }
}
