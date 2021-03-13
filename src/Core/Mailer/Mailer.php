<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\RawMessage;
use Windwalker\DI\Container;

/**
 * The Mailer class.
 */
class Mailer implements MailerInterface
{
    /**
     * Mailer constructor.
     *
     * @param  TransportInterface  $transport
     * @param  Container           $container
     */
    public function __construct(
        protected TransportInterface $transport,
        protected Container $container,
    ) {
        //
    }

    /**
     * @inheritDoc
     */
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        $this->transport->send($message, $envelope);
    }

    public function createMessage(
        ?string $subject = null,
        Headers $headers = null,
        AbstractPart $body = null
    ): MailMessage {
        $message = new MailMessage($this, $headers, $body);

        if ($subject !== null) {
            $message->subject($subject);
        }

        return $message;
    }
}
