<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use DomainException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\Utilities\Arr;

/**
 * The MailerManager class.
 *
 * @method Mailer get(?string $name = null, ...$args)
 */
#[Isolation]
class MailerManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'mail';
    }

    public function createMailer(array $options = []): MailerInterface
    {
        if (!class_exists(Transport::class)) {
            throw new DomainException('Please install symfony/mailer first.');
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
