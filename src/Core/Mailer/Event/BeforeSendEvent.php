<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer\Event;

use Symfony\Component\Mailer\Envelope;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BeforeSendEvent class.
 */
class BeforeSendEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public MailMessage $message,
        public MailerInterface $mailer,
        public ?Envelope $envelope = null,
        public int $flags = 0
    ) {
    }

    /**
     * @return int
     *
     * @deprecated  Use property instead.
     */
    public function &getFlags(): int
    {
        return $this->flags;
    }
}
