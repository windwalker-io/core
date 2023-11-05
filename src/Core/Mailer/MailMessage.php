<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use LogicException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Utilities\Classes\FlowControlTrait;

/**
 * The MailMessage class.
 */
class MailMessage extends Email
{
    use FlowControlTrait;

    protected AssetService|null $asset = null;

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

    public function send(?Envelope $envelope = null, int $flags = 0): SentMessage
    {
        if ($this->mailer === null) {
            throw new LogicException(
                sprintf(
                    '%s must set into %s if you use $message->send()',
                    Mailer::class,
                    static::class
                )
            );
        }

        return $this->mailer->send($this, $envelope, $flags);
    }

    public function renderBody(string $path, array $data = []): static
    {
        if ($this->mailer instanceof RenderableMailerInterface) {
            $data['message'] = $this;
            $data['asset'] = $this->asset ??= $this->mailer->createAssetService();

            $this->html(
                $this->mailer->renderBody(
                    $path,
                    $data
                )
            );
        }

        return $this;
    }

    /**
     * @return AssetService|null
     */
    public function getAsset(): ?AssetService
    {
        return $this->asset;
    }
}
