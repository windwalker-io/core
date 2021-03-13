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
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
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
        $dsn = 'smtp://569185ae5c1b3a23c:7a284a24246524@smtp.mailtrap.io:2525';

        $transport = Transport::fromDsn($dsn);

        $mailer = new Mailer(new \Symfony\Component\Mailer\Mailer($transport));
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
            ->send();

        return 0;
    }
}
