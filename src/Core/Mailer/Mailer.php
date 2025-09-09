<?php

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
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Mailer\Event\BeforeSendEvent;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DI\Container;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filter\OutputFilter;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Options\OptionsResolverTrait;

use function Windwalker\str;

/**
 * The Mailer class.
 */
class Mailer implements MailerInterface, RenderableMailerInterface, EventAwareInterface
{
    use CoreEventAwareTrait;
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

    public function htmlToTextMessage(string $html): string
    {
        return (string) str($html)
            ->apply(OutputFilter::stripStyle(...))
            ->apply(OutputFilter::cleanText(...))
            ->stripHtmlTags('<a>')
            ->collapseWhitespaces()
            ->replace('&nbsp;', '');
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'cc' => '',
                'bcc' => '',
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function send(MailMessage|RawMessage $message, ?Envelope $envelope = null, int $flags = 0): SentMessage
    {
        $envelope ??= $this->envelop;

        // $this->prepareMessage($message, $flags);

        $mailer = $this;
        $event = $this->emit(
            new BeforeSendEvent(
                message: $message,
                mailer: $mailer,
                envelope: $envelope,
                flags: $flags
            )
        );

        $flags = $event->flags;
        $envelope = $event->envelope;
        $message = $event->message;

        if ($flags & static::IGNORE_ENVELOPE) {
            $envelope = null;
        }

        $enabled = env('MAIL_ENABLED');

        if ($flags & static::FORCE_SEND || $enabled) {
            return $this->transport->send($message, $envelope);
        }

        return new SentMessage($message, Envelope::create($message));
    }

    public function prepareMessage(RawMessage $message, int $flags = 0): void
    {
        if (!$message instanceof MailMessage) {
            return;
        }

        $from = $message->getFrom();

        if ($from === []) {
            $message->from($this->container->getParam('mail.from'));
        }

        if ($message->getReplyTo() === []) {
            if ($reply = $this->container->getParam('mail.reply_to')) {
                $message->replyTo($reply);
            }
        }

        if ($flags & static::IGNORE_AUTO_CC) {
            $this->handleAutoCC($message, 'cc');
            $this->handleAutoCC($message, 'bcc');
        }

        if ($message->getTextBody() === null) {
            $message->text($this->htmlToTextMessage($message->getHtmlBody()));
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
        ?Headers $headers = null,
        ?AbstractPart $body = null
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

    /**
     * renderBody
     *
     * @param  string  $layout
     * @param  array   $data
     * @param  array   $options
     *
     * @return  string
     */
    public function renderBody(string $layout, array $data = [], array $options = []): string
    {
        return $this->container->get(RendererService::class)->render(
            $layout,
            $data,
            $options
        );
    }

    public function createAssetService(): AssetService
    {
        return $this->container->newInstance(AssetService::class);
    }

    /**
     * @return TransportInterface
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * @param  TransportInterface  $transport
     *
     * @return  static  Return self to support chaining.
     */
    public function setTransport(TransportInterface $transport): static
    {
        $this->transport = $transport;

        return $this;
    }

    public function buildBody(\Closure $handler, ?string $layout = null): string
    {
        $builder = $this->container->newInstance(MailBuilder::class);

        $layout ??= $this->container->getParam('mail.builder.layout') ?? 'mail.mail-layout';

        $body = $this->container->call($handler, ['mailer' => $this, 'builder' => $builder]) ?? $builder;

        return $this->renderBody(
            $layout,
            [
                'content' => (string) $body,
                'builder' => $body,
            ]
        );
    }
}
