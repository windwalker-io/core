<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Factory\MailerFactory;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\DI\Attributes\Isolation;

/**
 * The MailerManager class.
 *
 * @method  Mailer  get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class MailerManager extends MailerFactory
{
    //
}
