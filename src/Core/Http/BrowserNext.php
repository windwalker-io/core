<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use WhichBrowser\Parser;

use function Windwalker\tap;

class BrowserNext extends Parser
{
    public ?string $userAgent {
        get => $this->headers['user-agent'] ?? null;
    }

    public static function fromRequest(ServerRequestInterface $request): static
    {
        $headers = [];

        foreach ($request->getHeaders() as $header => $values) {
            $headers[$header] = $request->getHeaderLine($header);
        }

        return new static($headers, $request->getHeaderLine('User-Agent'));
    }

    public function with(
        array|ServerRequestInterface|null $headers = null,
        ?string $userAgent = null,
        array $options = []
    ): static {
        if ($headers instanceof ServerRequestInterface) {
            return static::fromRequest($headers);
        }

        $new = clone $this;
        $headers ??= $this->headers;

        if ($userAgent !== null) {
            $this->headers['user-agent'] = trim($userAgent);
        }

        $this->headers = array_change_key_case($headers, CASE_LOWER);

        $new->analyse($this->headers, $options);

        return $new;
    }

    public function __construct(
        public protected(set) array $headers = [],
        ?string $userAgent = null,
        $options = []
    ) {
        if ($userAgent !== null) {
            $this->headers['user-agent'] = trim($userAgent);
        }

        $this->headers = array_change_key_case($this->headers, CASE_LOWER);

        parent::__construct($this->headers, $options);
    }

    public function isRobot(): bool
    {
        return $this->device->type === 'bot' || $this->device->type === 'robot';
    }

    public function osString(): string
    {
        $hidden = $this->os->hidden;
        $this->os->hidden = false;

        return tap(
            $this->os->toString(),
            fn () => $this->os->hidden = $hidden
        );
    }

    public function deviceString(): string
    {
        $hidden = $this->device->hidden;
        $this->device->hidden = false;

        return tap(
            $this->device->toString(),
            fn () => $this->device->hidden = $hidden
        );
    }

    public function browserString(): string
    {
        $hidden = $this->browser->hidden;
        $this->browser->hidden = false;

        return tap(
            $this->browser->toString(),
            fn () => $this->browser->hidden = $hidden
        );
    }
}
