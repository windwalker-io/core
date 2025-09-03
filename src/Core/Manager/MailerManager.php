<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Windwalker\Core\Factory\MailerFactory;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\DI\Attributes\Isolation;

/**
 * The MailerManager class.
 *
 * @method Mailer get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class MailerManager extends MailerFactory
{
    //
}
