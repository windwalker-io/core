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
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * The Mailer class.
 */
class Mailer implements MailerInterface
{
    protected SymfonyMailer $mailer;

    /**
     * Mailer constructor.
     *
     * @param  TransportInterface             $transport
     * @param  MessageBusInterface|null       $bus
     * @param  EventDispatcherInterface|null  $dispatcher
     */
    public function __construct(
        TransportInterface $transport,
        MessageBusInterface $bus = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->mailer = new SymfonyMailer($transport, $bus, $dispatcher);
    }

    /**
     * @inheritDoc
     */
    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        $this->mailer->send($message, $envelope);
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
