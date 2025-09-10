<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailerOptions;
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

    public static function mailer(array|MailerOptions|\Closure $options = []): \Closure
    {
        return #[Factory] function (Container $container) use ($options) {
            if (!class_exists(Transport::class)) {
                throw new \DomainException('Please install symfony/mailer first.');
            }

            if ($options instanceof \Closure) {
                $options = $options();
            }

            $options = MailerOptions::wrapWith($options);
            $dsn = $options->getDsn();

            if (is_string($dsn)) {
                $transport = Transport::fromDsn($dsn);
            } else {
                $factory = $container->get(Transport::class);
                $transport = $factory->fromDsnObject($dsn);
            }

            return new Mailer(
                $transport,
                $container,
                $options->getEnvelope(),
                $options,
            );
        };
    }

    public function createMailer(array $options = []): MailerInterface
    {
        return $this->container->call(static::createMailer($options));
    }

    public static function createEnvelope(array $options): ?Envelope
    {
        if (!$options['sender']) {
            return null;
        }

        $options = Mailer::wrapAddresses($options);

        return new Envelope($options['sender'], $options['recipients']);
    }
}
