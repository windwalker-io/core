<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use LogicException;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\TextPart;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Utilities\Classes\ChainingTrait;
use Windwalker\Utilities\Str;

/**
 * The MailMessage class.
 */
class MailMessage extends Email
{
    use ChainingTrait;

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
        ?Headers $headers = null,
        ?AbstractPart $body = null
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

    public function toString(bool $debug = false): string
    {
        if (!$debug || !$this->mailer instanceof RenderableMailerInterface) {
            return parent::toString();
        }

        $message = clone $this;

        $this->mailer->prepareMessage($message);

        return $message->toString();
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

    public function buildBody(\Closure $handler, ?string $layout = null): static
    {
        $body = $this->mailer->buildBody($handler, $layout);

        return $this->html($body);
    }

    public function debugPrintBody(bool $die = false): static
    {
        echo $this->getHtmlBody();

        if ($die) {
            die;
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

    public function attachPsr7(StreamInterface $body, ?string $name = null, ?string $contentType = null): static
    {
        return $this->attach($body->getContents(), $name, $contentType);
    }

    public function unsubscribe(
        string|\Stringable|null $http,
        string|\Stringable|null $mailto = null,
        bool $oneClick = false
    ): static {
        if ($oneClick) {
            $this->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        $links = [];

        if ($http) {
            $links[] = '<' . $http . '>';
        }

        if ($mailto) {
            $links[] = '<' . Str::ensureLeft($mailto, 'mailto:') . '>';
        }

        if ($links !== []) {
            $this->getHeaders()->addTextHeader('List-Unsubscribe', implode(', ', $links));
        }

        return $this;
    }
}
