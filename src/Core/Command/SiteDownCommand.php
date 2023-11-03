<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\Offline\OfflineConfig;
use Windwalker\Core\Application\Offline\OfflineManager;
use Windwalker\Utilities\Arr;

/**
 * The SiteDownCommand class.
 */
#[CommandWrapper(description: 'Make website offline to maintenance mode.')]
class SiteDownCommand implements CommandInterface
{
    /**
     * MailTestCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app, protected OfflineManager $offlineManager)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addOption(
            'allow-ips',
            'p',
            InputOption::VALUE_REQUIRED,
            'The allowed IPs',
        );
        $command->addOption(
            'redirect',
            'r',
            InputOption::VALUE_REQUIRED,
            'The redirect URL.'
        );
        $command->addOption(
            'secret',
            's',
            InputOption::VALUE_REQUIRED,
            'The secret to bypass maintenance mode.'
        );
        $command->addOption(
            'template',
            't',
            InputOption::VALUE_NONE,
            'The maintenance page template.'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if ($this->offlineManager->isOffline()) {
            $io->writeln('This site is already offline. Update offline config.');
        }

        $config = new OfflineConfig();

        $allowedIps = (string) $io->getOption('allow-ips');
        $allowedIps = Arr::explodeAndClear(',', $allowedIps);

        $config->addAllowedIps(...$allowedIps);
        $config->setRedirect((string) $io->getOption('redirect'));
        $config->setSecret((string) $io->getOption('secret'));
        $config->setTemplate((string) $io->getOption('template'));

        $this->offlineManager->makeOffline($config);

        $io->writeln('Site offline success.');

        return 0;
    }
}
