<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;

/**
 * The MailTestCommand class.
 */
#[CommandWrapper(description: 'This command will send a test mail by your mail settings.')]
class MailTestCommand implements CommandInterface
{
    /**
     * MailTestCommand constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  Mailer                $mailer
     */
    public function __construct(protected ApplicationInterface $app, protected Mailer $mailer)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'to',
            InputArgument::IS_ARRAY,
            'The recipients.'
        );
        $command->addOption(
            'message',
            'm',
            InputOption::VALUE_REQUIRED,
            'Message to add to mail body.',
        );
        $command->addOption(
            'subject',
            's',
            InputOption::VALUE_REQUIRED,
            'Mail subject title.'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        $custom = $io->getOption('message');
        $subject = $io->getOption('subject');

        if ($custom) {
            $custom = '<p><strong>Custom message:</strong> ' . $custom . '</p>';
        }

        $body = sprintf($this->getBody(), $custom);
        $from = $this->app->config('mail.from');

        $recipients = $io->getArgument('to');

        if ($recipients === []) {
            $recipients = [$from];
        }

        $date = new \DateTime('now', new \DateTimeZone($this->app->config('app.server_timezone')));
        $date->setTimezone(new \DateTimeZone($this->app->config('app.timezone')));

        $io->writeln('Sending...');

        $title = sprintf(
            '[Windwalker] Test Message%s - %s',
            $subject ? ': ' . $subject : '',
            $date->format('Y-m-d H:i:s')
        );

        $this->mailer->createMessage($title)
            ->to(...$recipients)
            ->from($from)
            ->html($body)
            ->send(null, MailerInterface::FORCE_SEND);

        $io->writeln(sprintf('Test mail sent to: <info>%s</info>.', implode(' ', $recipients)));

        return 0;
    }

    /**
     * getBody
     *
     * @return  string
     */
    protected function getBody()
    {
        return <<<HTML
<p>Hello</p>

<p>This is a test mail from Windwalker. If you receive this mail, it means you have your mail settings correct.</p>

%s

<p>Have a good day.</p>
HTML;
    }
}
