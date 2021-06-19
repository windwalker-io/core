<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Security;

use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\DOM\DOMElement;
use Windwalker\Session\Session;

use function Windwalker\DOM\h;

/**
 * The CsrfService class.
 */
class CsrfService
{
    protected string $tokenKey = 'csrf-token';

    /**
     * CsrfService constructor.
     *
     * @param  Config   $config
     * @param  Session  $session
     */
    public function __construct(protected Config $config, protected Session $session)
    {
        //
    }

    public function validate(AppRequest $request, ?string $message = null): void
    {
        if (!$this->checkToken($request)) {
            throw new InvalidTokenException($message ?? 'Invalid CSRF Token');
        }
    }

    public function input(array $attrs = []): DOMElement
    {
        $attrs['type']  = 'hidden';
        $attrs['name']  = $this->getToken();
        $attrs['value'] = 1;
        $attrs['class'] = $attrs['class'] ?? 'anticsrf';

        return h('input', $attrs);
    }

    public function getToken(bool $refresh = false): string
    {
        $this->session->start();

        $key = $this->getTokenKey();

        $token = $this->session->get($key);

        if (!$token || $refresh) {
            $token = $this->createToken();

            $this->session->set($key, $token);
        }

        return $token;
    }

    /**
     * createToken
     *
     * @return  string
     *
     * @throws \Exception
     */
    public function createToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function checkToken(AppRequest $request, ?string $method = null): bool
    {
        $method ??= 'REQUEST';

        $token = $this->getToken();

        $tokenValue = $request->inputWithMethod($method, $token);

        if ($tokenValue !== null) {
            return true;
        }

        return $request->getHeader('X-CSRF-Token') === $token;
    }

    /**
     * @return string
     */
    public function getTokenKey(): string
    {
        return $this->tokenKey;
    }

    /**
     * @param  string  $tokenKey
     *
     * @return  static  Return self to support chaining.
     */
    public function setTokenKey(string $tokenKey): static
    {
        $this->tokenKey = $tokenKey;

        return $this;
    }
}
