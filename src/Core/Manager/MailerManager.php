<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use DomainException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Address;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Utilities\Arr;

/**
 * The MailerManager class.
 */
class MailerManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'mail';
    }

    public function createMailer(array $options = []): MailerInterface
    {
        if (!class_exists(Transport::class)) {
            throw new DomainException('Please install symfony/mailer ^5.0||^6.0 first.');
        }

        if (!env('MAIL_ENABLED')) {
            $options['dsn'] = 'null://null';
        }

        $options = Arr::mergeRecursive(
            [
                'envelope' => [
                    'sender' => null,
                    'recipients' => [],
                ],
                'dsn' => [
                    'scheme' => '',
                    'host' => '',
                    'user' => '',
                    'password' => '',
                    'port' => '',
                    'options' => [],
                ],

                // Auto CC to emails, use (,) separate addresses.
                'cc' => '',

                // Auto BCC to emails, use (,) separate addresses.
                'bcc' => '',
            ],
            $options
        );

        $options['dsn'] = $options['dsn'] ?: 'null://null';

        if (!is_array($options['dsn'])) {
            $transport = Transport::fromDsn($options['dsn']);
        } else {
            $dsn = new Dsn(...$options['dsn']);

            $factory = $this->container->get(Transport::class);
            $transport = $factory->fromDsnObject($dsn);
        }

        return new Mailer(
            $transport,
            $this->container,
            $this->createEnvelope($options['envelope']),
        );
    }

    public function createEnvelope(array $options): ?Envelope
    {
        if (!$options['sender']) {
            return null;
        }

        $options = Mailer::wrapAddresses($options);

        return new Envelope($options['sender'], $options['recipients']);
    }
}
