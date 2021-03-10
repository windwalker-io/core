<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Windwalker\Core\Console\CommandWrapperInterface;
use Windwalker\Core\Console\IOInterface;

/**
 * The MigrationWrapper class.
 */
class MigrationWrapper implements CommandWrapperInterface
{
    /**
     * MigrationWrapper constructor.
     *
     * @param  CommandWrapperInterface  $child
     */
    public function __construct(protected CommandWrapperInterface $child)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {


        $this->child->configure($command);
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): mixed
    {
        // ini_set('max_execution_time', '0');

        return $this->child->execute($io);
    }
}
