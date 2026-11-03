<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Session\FlashBag;

/**
 * Minimal Session double exposing only the addFlash()/getFlashBag() API that
 * AppContext::addMessage()/getMessages() rely on, without needing a real
 * PHP session (bridge/handler) to be started.
 */
class SessionStub
{
    private FlashBag $flashBag;

    public function __construct()
    {
        $this->flashBag = new FlashBag();
    }

    public function addFlash(array|string $messages, string $type = 'info'): static
    {
        foreach ((array) $messages as $message) {
            $this->flashBag->add($message, $type);
        }

        return $this;
    }

    public function getFlashBag(): FlashBag
    {
        return $this->flashBag;
    }
}
