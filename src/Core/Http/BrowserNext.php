<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use WhichBrowser\Parser;

class BrowserNext extends Parser
{
    public static function fromRequest(ServerRequestInterface $request): static
    {
        $headers = [];

        foreach ($request->getHeaders() as $header => $values) {
            $headers[$header] = $request->getHeaderLine($header);
        }

        return new static($request->getHeaderLine('User-Agent'), $headers);
    }

    public function withParse(string|ServerRequestInterface $userAgent, ?array $headers = null): static
    {
        if ($userAgent instanceof ServerRequestInterface) {
            return static::fromRequest($userAgent);
        }

        return new static($userAgent, $headers ?? $this->headers);
    }

    public function __construct(
        public protected(set) string $userAgent,
        public protected(set) array $headers = [],
        $options = []
    ) {
        $this->headers['user-agent'] = $this->userAgent;

        parent::__construct($this->headers, $options);
    }

    public function isRobot(): bool
    {
        return $this->device->type === 'bot' || $this->device->type === 'robot';
    }

    public function osString(): string
    {
        $this->os->hidden = false;

        return $this->os->toString();
    }

    public function deviceString(): string
    {
        $this->device->hidden = false;

        return $this->device->toString();
    }

    public function browserString(): string
    {
        $this->browser->hidden = false;

        return $this->browser->toString();
    }
}
