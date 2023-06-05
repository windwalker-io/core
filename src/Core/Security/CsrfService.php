<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Security;

use Exception;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Language\TranslatorTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\DOM\DOMElement;
use Windwalker\Session\Session;

use function Windwalker\DOM\h;

/**
 * The CsrfService class.
 */
class CsrfService
{
    use TranslatorTrait;

    protected string $tokenKey = 'csrf-token';

    /**
     * CsrfService constructor.
     *
     * @param  Config   $config
     * @param  Session  $session
     */
    public function __construct(
        protected Config $config,
        protected Session $session,
        protected LangService $langService
    ) {
        //
    }

    public function validate(AppRequest $request, ?string $method = null, ?string $message = null): void
    {
        if (!$this->checkToken($request, $method)) {
            throw new InvalidTokenException($message ?? $this->getInvalidMessage(), 403);
        }
    }

    public function input(array $attrs = []): DOMElement
    {
        $attrs['type'] = 'hidden';
        $attrs['name'] = $this->getToken();
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
     * @throws Exception
     */
    public function createToken(): string
    {
        return Base64Url::encode(random_bytes(32));
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

    /**
     * @return string
     */
    public function getInvalidMessage(): string
    {
        $this->langService::checkLanguageInstalled();

        return $this->trans('windwalker.security.message.csrf.invalid');
    }
}
