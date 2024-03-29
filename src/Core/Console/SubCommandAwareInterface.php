<?php

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Interface SubCommandAwareInterface
 */
interface SubCommandAwareInterface
{
    public function configureSubCommand(Command $command, InputInterface $input): void;
}
