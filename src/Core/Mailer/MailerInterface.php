<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

/**
 * Interface MailerInterface
 */
interface MailerInterface
{
    public const FORCE_SEND = 1;

    public const IGNORE_AUTO_CC = 1 << 1;

    public const IGNORE_ENVELOPE = 1 << 2;

    public function createMessage(
        ?string $subject = null,
        ?Headers $headers = null,
        ?AbstractPart $body = null
    ): MailMessage;

    /**
     * @param  MailMessage    $message
     * @param  Envelope|null  $envelope
     * @param  int            $flags
     *
     * @return SentMessage
     * @throws TransportExceptionInterface
     */
    public function send(MailMessage $message, ?Envelope $envelope = null, int $flags = 0): SentMessage;
}
