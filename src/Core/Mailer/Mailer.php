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
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\DI\Container;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The Mailer class.
 */
class Mailer implements MailerInterface
{
    use OptionsResolverTrait;

    /**
     * Mailer constructor.
     *
     * @param  TransportInterface  $transport
     * @param  Container           $container
     * @param  Envelope|null       $envelop
     * @param  array               $options
     */
    public function __construct(
        protected TransportInterface $transport,
        protected Container $container,
        protected ?Envelope $envelop = null,
        array $options = []
    ) {
        $this->resolveOptions($options, [$this, 'configureOptions']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'cc' => '',
                'bcc' => ''
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function send(RawMessage $message, Envelope $envelope = null, int $flags = 0): SentMessage
    {
        $envelope ??= $this->envelop;

        if ($flags & static::IGNORE_ENVELOPE) {
            $envelope = null;
        }

        $this->prepareMessage($message, $flags);

        if ($flags & static::FORCE_SEND || $this->container->getParam('mail.enabled')) {
            return $this->transport->send($message, $envelope);
        }

        return new SentMessage($message, $envelope);
    }

    protected function prepareMessage(RawMessage $message, int $flags = 0): void
    {
        if (!$message instanceof MailMessage) {
            return;
        }

        $from = $message->getFrom();

        if ($from === []) {
            $message->from(
                $this->container->getParam('mail.from')
            );
        }

        if ($flags & static::IGNORE_AUTO_CC) {
            $this->handleAutoCC($message, 'cc');
            $this->handleAutoCC($message, 'bcc');
        }
    }

    protected function handleAutoCC(MailMessage $message, string $type = 'cc'): void
    {
        $cc = $this->getOption($type);

        if (is_string($cc)) {
            $cc = Arr::explodeAndClear(',', $cc);
        }

        if ($cc !== []) {
            $message->addCc(...$cc);
        }
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

    /**
     * @return Envelope|null
     */
    public function getEnvelop(): ?Envelope
    {
        return $this->envelop;
    }

    /**
     * @param  Envelope|null  $envelop
     *
     * @return  static  Return self to support chaining.
     */
    public function setEnvelop(?Envelope $envelop): static
    {
        $this->envelop = $envelop;

        return $this;
    }

    public static function wrapAddresses(array $addresses): array
    {
        foreach ($addresses as &$address) {
            if (is_string($address)) {
                $address = Address::create($address);
            } elseif (is_array($address)) {
                $address = static::wrapAddresses($address);
            }
        }

        return $addresses;
    }
}
