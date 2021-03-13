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
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;

/**
 * The MailMessage class.
 */
class MailMessage extends Email
{
    /**
     * MailMessage constructor.
     *
     * @param  MailerInterface|null  $mailer
     * @param  Headers|null          $headers
     * @param  AbstractPart|null     $body
     */
    public function __construct(
        protected ?MailerInterface $mailer = null,
        Headers $headers = null,
        AbstractPart $body = null
    ) {
        parent::__construct($headers, $body);
    }

    public function send(?Envelope $envelope = null): static
    {
        if ($this->mailer === null) {
            throw new \LogicException(
                sprintf(
                    '%s must set into %s if you use $message->send()',
                    Mailer::class,
                    static::class
                )
            );
        }

        $this->mailer->send($this, $envelope);

        return $this;
    }
}
