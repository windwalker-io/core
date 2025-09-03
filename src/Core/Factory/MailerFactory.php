<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\Utilities\Arr;

#[Isolation]
class MailerFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function getConfigPrefix(): string
    {
        return 'mail';
    }

    public static function mailer(array $options = []): \Closure
    {
        return #[Factory] function (Container $container) use ($options) {
            if (!class_exists(Transport::class)) {
                throw new \DomainException('Please install symfony/mailer first.');
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

                $factory = $container->get(Transport::class);
                $transport = $factory->fromDsnObject($dsn);
            }

            return new Mailer(
                $transport,
                $container,
                $this->createEnvelope($options['envelope']),
            );
        };
    }

    public function createMailer(array $options = []): MailerInterface
    {
        return $this->container->call(static::createMailer($options));
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
