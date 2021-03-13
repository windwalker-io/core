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
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Mailer\Mailer;

/**
 * The MailTestCommand class.
 */
#[CommandWrapper(description: 'Test send mail.')]
class MailTestCommand implements CommandInterface
{
    /**
     * MailTestCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        $dsn = 'smtp://:@smtp.mailtrap.io:2525';

        $transport = Transport::fromDsn($dsn);

        $mailer = new Mailer($transport, $this->app->getContainer());
        $mailer->createMessage('YYY')
            ->to(
                'test@mail.com',
                'asika@test.com'
            )
            ->from('noreply@yoo.com')
            ->attachFromPath(
                $this->app->path('@temp/cat.jpg'),
                'cat'
            )
            // ->embed(fopen($this->app->path('@temp/cat.jpg'), 'r'), 'cat')
            ->html(
                '<p>Hi YOO</p> <img src="cid:cat">',
            )
            ->send(
                new Envelope(
                    new Address('sender@sender.co'),
                    [
                        new Address('re@re.com')
                    ]
                )
            );

        return 0;
    }
}
