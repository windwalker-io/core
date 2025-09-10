<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Command;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;

#[CommandWrapper(
    description: 'Packages post install command.',
    hidden: true
)]
class PackagePostInstallCommand implements CommandInterface
{
    public function __construct(protected ConsoleApplication $app)
    {
    }

    public function configure(Command $command): void
    {
        //
    }

    public function execute(IOInterface $io): int
    {
        $this->app->runProcess(
            '@php windwalker pkg:install --tag config --no-details',
            null,
            $io
        );

        $this->app->runProcess(
            '@php windwalker asset:sync',
            null,
            $io
        );

        return 0;
    }
}
