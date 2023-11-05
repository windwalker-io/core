<?php

declare(strict_types=1);

namespace Windwalker\Core\Command;

use DateTime;
use DateTimeZone;
use DomainException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Manager\MailerManager;

/**
 * The MailTestCommand class.
 */
#[CommandWrapper(description: 'Make site online.')]
class SiteUpCommand implements CommandInterface
{
    public function __construct(protected ApplicationInterface $app, protected MaintenanceManager $offlineManager)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if (!$this->offlineManager->isDown()) {
            $io->writeln('No actions. This site is currently online.');
            return 1;
        }

        $this->offlineManager->up();

        $io->writeln('Site online success.');

        return 0;
    }
}
