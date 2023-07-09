<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Security;

use Windwalker\Crypt\Password;
use Windwalker\Utilities\Str;

/**
 * The CspNonceService class.
 */
class CspNonceService
{
    protected ?string $nonce = null;

    public function attr(): string
    {
        return sprintf(
            'nonce="%s"',
            $this->getNonce()
        );
    }

    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = static::generateNonce();
        }

        return $this->nonce;
    }

    public static function generateNonce(): string
    {
        return Password::genRandomPassword(32);
    }

    /**
     * @param  string|null  $nonce
     *
     * @return  static  Return self to support chaining.
     */
    public function setNonce(?string $nonce): static
    {
        $this->nonce = $nonce;

        return $this;
    }


    /**
     * Parse HTML and auto add nonce.
     *
     * NOTE: DO NOT USE THIS METHOD PARSE WHOLE BODY.
     *
     * @param  string  $body
     * @param  string  $nonce
     * @param  array   $tags
     *
     * @return  string
     */
    public static function addNonceToTags(string $body, string $nonce, array $tags): string
    {
        foreach ($tags as $tag) {
            $body = preg_replace_callback(
                "#(<{$tag}[^>]*)(>.*?</{$tag}>|>)#",
                static function ($matches) use ($nonce) {
                    [$all, $start, $end] = $matches;

                    if (str_ends_with($start, '/')) {
                        $start = Str::removeRight($start, '/');
                        $end = ' /' . $end;
                    }

                    if (!str_contains($start, 'nonce="')) {
                        return $start . ' nonce="' . $nonce . '"' . $end;
                    }

                    return $all;
                },
                $body
            );
        }

        return $body;
    }

    /**
     * Parse HTML and auto add nonce to elements with no* events.
     *
     * NOTE: DO NOT USE THIS METHOD PARSE WHOLE BODY.
     *
     * @param  string  $body
     * @param  string  $nonce
     *
     * @return  string
     */
    public static function addNonceToTagWithEvents(string $body, string $nonce): string
    {
        $body = preg_replace_callback(
            '/(<\w+\s[^>]*\s+on\w+="[^"]*"[^>]*)>/i',
            static function ($matches) use ($nonce) {
                [$all, $start] = $matches;
                $end = '>';

                if (str_ends_with($start, '/')) {
                    $start = Str::removeRight($start, '/');
                    $end = ' /' . $end;
                }

                if (!str_contains($start, 'nonce="')) {
                    return $start . ' nonce="' . $nonce . '"' . $end;
                }

                return $all;
            },
            $body
        );

        return $body;
    }

    /**
     * Parse HTML and add nonce to elements with inline style.
     *
     * NOTE: DO NOT USE THIS METHOD PARSE WHOLE BODY.
     *
     * @param  string  $body
     * @param  string  $nonce
     *
     * @return  string
     */
    public static function addNonceToTagWithStyle(string $body, string $nonce): string
    {
        $body = preg_replace_callback(
            '/(<\w+\s[^>]*\s+style="[^"]*"[^>]*)>/i',
            static function ($matches) use ($nonce) {
                [$all, $start] = $matches;
                $end = '>';

                if (str_ends_with($start, '/')) {
                    $start = Str::removeRight($start, '/');
                    $end = ' /' . $end;
                }

                if (!str_contains($start, 'nonce="')) {
                    return $start . ' nonce="' . $nonce . '"' . $end;
                }

                return $all;
            },
            $body
        );

        return $body;
    }
}
