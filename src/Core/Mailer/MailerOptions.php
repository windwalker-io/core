<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Transport\Dsn;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Options\RecordOptionsTrait;

class MailerOptions
{
    use RecordOptionsTrait;

    public function __construct(
        public array|Envelope|null $envelope = null,
        public array|string|Dsn|null $dsn = null,
        public array|string|null $cc = null,
        public array|string|null $bcc = null,
    ) {
    }

    public function getEnvelope(): ?Envelope
    {
        if (!$this->envelope) {
            return null;
        }

        if (is_array($this->envelope)) {
            if (empty($this->envelope['sender'])) {
                return null;
            }

            $envelope = Mailer::wrapAddresses($this->envelope);

            return new Envelope($envelope['sender'], $envelope['recipients']);
        }

        return $this->envelope;
    }

    public function getDsn(): Dsn|string
    {
        if (!$this->dsn) {
            return 'null://null';
        }

        if (is_array($this->dsn)) {
            return new Dsn(...$this->dsn);
        }

        if (is_string($this->dsn)) {
            return Dsn::fromString($this->dsn);
        }

        return $this->dsn;
    }

    public function getCcList(): array
    {
        if ($this->cc === null) {
            return [];
        }

        if (is_string($this->cc)) {
            return Arr::explodeAndClear(',', $this->cc);
        }

        return $this->cc;
    }

    public function getBccList(): array
    {
        if ($this->bcc === null) {
            return [];
        }

        if (is_string($this->bcc)) {
            return Arr::explodeAndClear(',', $this->bcc);
        }

        return $this->bcc;
    }
}
