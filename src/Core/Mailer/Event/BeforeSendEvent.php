<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Mailer\Event;

use Symfony\Component\Mailer\Envelope;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Event\AbstractEvent;

/**
 * The BeforeSendEvent class.
 */
class BeforeSendEvent extends AbstractEvent
{
    protected MailMessage $message;

    protected MailerInterface $mailer;

    protected ?Envelope $envelope = null;

    protected int $flags = 0;

    /**
     * @return MailMessage
     */
    public function getMessage(): MailMessage
    {
        return $this->message;
    }

    /**
     * @param  MailMessage  $message
     *
     * @return  static  Return self to support chaining.
     */
    public function setMessage(MailMessage $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return MailerInterface
     */
    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    /**
     * @param  MailerInterface  $mailer
     *
     * @return  static  Return self to support chaining.
     */
    public function setMailer(MailerInterface $mailer): static
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * @return Envelope|null
     */
    public function getEnvelope(): ?Envelope
    {
        return $this->envelope;
    }

    /**
     * @param  Envelope|null  $envelope
     *
     * @return  static  Return self to support chaining.
     */
    public function setEnvelope(?Envelope $envelope): static
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * @return int
     */
    public function &getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param  int  $flags
     *
     * @return  static  Return self to support chaining.
     */
    public function setFlags(int $flags): static
    {
        $this->flags = $flags;

        return $this;
    }
}
