<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

/**
 * Interface MailerInterface
 */
interface MailerInterface extends SymfonyMailerInterface
{
    public function createMessage(
        ?string $subject = null,
        Headers $headers = null,
        AbstractPart $body = null
    ): MailMessage;
}
