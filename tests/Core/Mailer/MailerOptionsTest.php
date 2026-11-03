<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Mailer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Address;
use Windwalker\Core\Mailer\MailerOptions;

#[CoversClass(MailerOptions::class)]
class MailerOptionsTest extends TestCase
{
    #[Test]
    public function getEnvelopeReturnsNullIfEnvelopeNotSet(): void
    {
        $options = new MailerOptions();

        self::assertNull($options->getEnvelope());
    }

    #[Test]
    public function getEnvelopeReturnsNullIfArrayHasNoSender(): void
    {
        $options = new MailerOptions(
            envelope: ['recipients' => ['foo@example.com']]
        );

        self::assertNull($options->getEnvelope());
    }

    #[Test]
    public function getEnvelopeWrapsArrayIntoEnvelope(): void
    {
        $options = new MailerOptions(
            envelope: [
                'sender' => 'sender@example.com',
                'recipients' => ['foo@example.com', 'bar@example.com'],
            ]
        );

        $envelope = $options->getEnvelope();

        self::assertInstanceOf(Envelope::class, $envelope);
        self::assertSame('sender@example.com', $envelope->getSender()->getAddress());
        self::assertCount(2, $envelope->getRecipients());
        self::assertSame('foo@example.com', $envelope->getRecipients()[0]->getAddress());
    }

    #[Test]
    public function getEnvelopeReturnsExistingEnvelopeAsIs(): void
    {
        $envelope = new Envelope(
            new Address('sender@example.com'),
            [new Address('foo@example.com')]
        );

        $options = new MailerOptions(envelope: $envelope);

        self::assertSame($envelope, $options->getEnvelope());
    }

    #[Test]
    public function getDsnReturnsDefaultNullTransportIfNotSet(): void
    {
        $options = new MailerOptions();

        self::assertSame('null://null', $options->getDsn());
    }

    #[Test]
    public function getDsnWrapsArrayIntoDsn(): void
    {
        $options = new MailerOptions(dsn: ['smtp', 'smtp.example.com', 'user', 'pass', 25]);

        $dsn = $options->getDsn();

        self::assertInstanceOf(Dsn::class, $dsn);
        self::assertSame('smtp', $dsn->getScheme());
        self::assertSame('smtp.example.com', $dsn->getHost());
    }

    #[Test]
    public function getDsnConvertsStringIntoDsn(): void
    {
        $options = new MailerOptions(dsn: 'smtp://user:pass@smtp.example.com:25');

        $dsn = $options->getDsn();

        self::assertInstanceOf(Dsn::class, $dsn);
        self::assertSame('smtp', $dsn->getScheme());
        self::assertSame('smtp.example.com', $dsn->getHost());
    }

    #[Test]
    public function getDsnReturnsExistingDsnAsIs(): void
    {
        $dsn = new Dsn('smtp', 'smtp.example.com');

        $options = new MailerOptions(dsn: $dsn);

        self::assertSame($dsn, $options->getDsn());
    }

    #[Test]
    public function getCcListReturnsEmptyArrayIfNull(): void
    {
        $options = new MailerOptions();

        self::assertSame([], $options->getCcList());
    }

    #[Test]
    public function getCcListExplodesStringIntoArray(): void
    {
        $options = new MailerOptions(cc: 'foo@example.com, bar@example.com');

        self::assertSame(['foo@example.com', 'bar@example.com'], $options->getCcList());
    }

    #[Test]
    public function getCcListReturnsArrayAsIs(): void
    {
        $options = new MailerOptions(cc: ['foo@example.com']);

        self::assertSame(['foo@example.com'], $options->getCcList());
    }

    #[Test]
    public function getBccListReturnsEmptyArrayIfNull(): void
    {
        $options = new MailerOptions();

        self::assertSame([], $options->getBccList());
    }

    #[Test]
    public function getBccListExplodesStringIntoArray(): void
    {
        $options = new MailerOptions(bcc: 'foo@example.com, bar@example.com');

        self::assertSame(['foo@example.com', 'bar@example.com'], $options->getBccList());
    }

    #[Test]
    public function getBccListReturnsArrayAsIs(): void
    {
        $options = new MailerOptions(bcc: ['foo@example.com']);

        self::assertSame(['foo@example.com'], $options->getBccList());
    }
}
