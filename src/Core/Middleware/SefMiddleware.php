<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Stream\Stream;
use Windwalker\Uri\Uri;

use const Windwalker\Stream\READ_WRITE_FROM_BEGIN;

/**
 * The SefMiddleware class.
 */
class SefMiddleware implements MiddlewareInterface
{
    public const REPLACE_LINK_HREF = 1 << 0;
    public const REPLACE_LINK_HASH = 1 << 1;
    public const REPLACE_SRC = 1 << 2;
    public const REPLACE_SRCSET = 1 << 3;
    public const REPLACE_POSTER = 1 << 4;
    public const REPLACE_WINDOW_OPEN = 1 << 5;
    public const REPLACE_STYLES = 1 << 6;
    public const REPLACE_OBJECTS = 1 << 7;
    public const REPLACE_ALL = self::REPLACE_LINK_HREF | self::REPLACE_LINK_HASH | self::REPLACE_SRC
        | self::REPLACE_SRCSET | self::REPLACE_POSTER | self::REPLACE_WINDOW_OPEN | self::REPLACE_STYLES
        | self::REPLACE_OBJECTS;

    public function __construct(
        protected AppContext $app,
        protected bool $enabled = true,
        protected ?string $baseHref = null,
        protected ?string $baseSrc = null,
        protected int $flags = self::REPLACE_ALL
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->enabled) {
            $newBody = $this->replaceRoutes((string) $response->getBody());

            $stream = new Stream('php://memory', READ_WRITE_FROM_BEGIN);
            $stream->write($newBody);

            $response = $response->withBody($stream);
        }

        return $response;
    }

    /**
     * @see https://github.com/joomla/joomla-cms/blob/staging/plugins/system/sef/sef.php
     *
     * @param string $buffer
     *
     * @return  string
     *
     * @since  1.8.13
     */
    protected function replaceRoutes(string $buffer): string
    {
        $uri = $this->app->getSystemUri();

        // Replace src links.
        $baseHref = $this->baseHref
            ?? rtrim($uri->path . $uri->script, '/') . '/';

        $baseSrc = $this->baseSrc ?? $uri->path;

        // For feeds we need to search for the URL with domain.

        // Search Hash anchor
        if (($this->flags & static::REPLACE_LINK_HASH) && str_contains($buffer, '#')) {
            $fullPath = (new Uri($uri->full()))->toString(Uri::PATH | Uri::QUERY);

            $regex  = '#\shref="\#(?!/|\')([^"]*)"#m';
            $buffer = preg_replace($regex, ' href="' . $fullPath . '#$1"', $buffer);
            $this->checkBuffer($buffer);
        }

        // Check for all unknown protocals
        // (a protocol must contain at least one alpahnumeric character followed by a ":").
        $protocols  = '[a-zA-Z0-9\-]+:';
        $attributes = [];

        if ($this->flags & static::REPLACE_LINK_HREF) {
            $attributes['href="'] = $baseHref;
        }

        if ($this->flags & static::REPLACE_SRC) {
            $attributes['src="'] = $baseHref;
        }

        if ($this->flags & static::REPLACE_POSTER) {
            $attributes['poster="'] = $baseHref;
        }

        if ($attributes !== []) {
            foreach ($attributes as $attribute => $base) {
                if (str_contains($buffer, $attribute)) {
                    $regex  = '#\s' . $attribute . '(?!/|' . $protocols . '|\#|\')([^"]*)"#m';
                    $buffer = preg_replace($regex, ' ' . $attribute . '"' . $base . '$1$2"', $buffer);
                    $this->checkBuffer($buffer);
                }
            }
        }

        if ($this->flags & static::REPLACE_SRCSET && str_contains($buffer, 'srcset=')) {
            $regex  = '#\s+srcset="([^"]+)"#m';
            $buffer = preg_replace_callback(
                $regex,
                static function ($match) use ($baseSrc, $protocols) {
                    preg_match_all('#(?:[^\s]+)\s*(?:[\d\.]+[wx])?(?:\,\s*)?#i', $match[1], $matches);

                    foreach ($matches[0] as &$src) {
                        $src = preg_replace('#^(?!/|' . $protocols . '|\#|\')(.+)#', $baseSrc . '$1', $src);
                    }

                    return ' srcset="' . implode($matches[0]) . '"';
                },
                $buffer
            );

            $this->checkBuffer($buffer);
        }

        // Replace all unknown protocals in javascript window open events.
        if (($this->flags & static::REPLACE_WINDOW_OPEN) && str_contains($buffer, 'window.open(')) {
            $regex  = '#onclick="window.open\(\'(?!/|' . $protocols . '|\#)([^/]+[^\']*?\')#m';
            $buffer = preg_replace($regex, 'onclick="window.open(\'' . $baseHref . '$1', $buffer);
            $this->checkBuffer($buffer);
        }

        // Replace all unknown protocols in onmouseover and onmouseout attributes.
        // $attributes = ['onmouseover=', 'onmouseout='];
        //
        // foreach ($attributes as $attribute) {
        //     if (str_contains($buffer, $attribute)) {
        //         $regex  = '#' . $attribute . '"this.src=([\']+)(?!/|' . $protocols . '|\#|\')([^"]+)"#m';
        //         $buffer = preg_replace($regex, $attribute . '"this.src=$1' . $baseSrc . '$2"', $buffer);
        //         $this->checkBuffer($buffer);
        //     }
        // }

        if (($this->flags & static::REPLACE_STYLES)) {
            $regexUrl = 'url\s*\(([\'\"]|\&\#0?3[49];)?(?!\/|\&\#0?3[49];|'
                . $protocols . '|\#)([^\)\'\"]+)([\'\"]|\&\#0?3[49];)?\)';

            // Replace all unknown protocols in CSS background image.
            if (str_contains($buffer, 'style=')) {
                $regex     = '#style=\s*([\'\"])(.*):' . $regexUrl . '#m';
                $buffer    = preg_replace($regex, 'style=$1$2: url($3' . $baseSrc . '$4$5)', $buffer);
                $this->checkBuffer($buffer);
            }

            // Replace style tags
            if (str_contains($buffer, '<style')) {
                /** @var null|string $buffer */
                $buffer = preg_replace_callback(
                    "'<style(.*?)>(.*?)</style>'is",
                    function ($matches) use ($baseSrc, $regexUrl) {
                        $css = $matches[2];

                        if (str_contains($css, 'url')) {
                            $css = preg_replace('#' . $regexUrl . '#is', 'url($1' . $baseSrc . '$2$3)', $css);
                        }

                        return '<style' . $matches[1] . '>' . $css . '</style>';
                    },
                    $buffer
                );

                $this->checkBuffer($buffer);
            }
        }

        if (($this->flags & static::REPLACE_OBJECTS)) {
            // Replace all unknown protocols in OBJECT param tag.
            if (str_contains($buffer, '<param')) {
                // OBJECT <param name="xx", value="yy"> -- fix it only inside the <param> tag.
                $regex  = '#(<param\s+)name\s*=\s*"(movie|src|url)"[^>]\s*value\s*=\s*"(?!/|' .
                    $protocols . '|\#|\')([^"]*)"#m';
                $buffer = preg_replace($regex, '$1name="$2" value="' . $baseSrc . '$3"', $buffer);
                $this->checkBuffer($buffer);

                // OBJECT <param value="xx", name="yy"> -- fix it only inside the <param> tag.
                $regex  = '#(<param\s+[^>]*)value\s*=\s*"(?!/|' . $protocols
                    . '|\#|\')([^"]*)"\s*name\s*=\s*"(movie|src|url)"#m';
                $buffer = preg_replace($regex, '<param value="' . $baseSrc . '$2" name="$3"', $buffer);
                $this->checkBuffer($buffer);
            }

            // Replace all unknown protocols in OBJECT tag.
            if (str_contains($buffer, '<object')) {
                $regex  = '#(<object\s+[^>]*)data\s*=\s*"(?!/|' . $protocols . '|\#|\')([^"]*)"#m';
                $buffer = preg_replace($regex, '$1data="' . $baseSrc . '$2"', $buffer);
                $this->checkBuffer($buffer);
            }
        }

        return $buffer;
    }

    /**
     * @param ?string $buffer
     *
     * @return  void
     *
     * @since  1.8.13
     */
    private function checkBuffer(?string $buffer): void
    {
        if ($buffer === null) {
            $message = match (preg_last_error()) {
                PREG_BACKTRACK_LIMIT_ERROR => 'PHP regular expression limit reached (pcre.backtrack_limit)',
                PREG_RECURSION_LIMIT_ERROR => 'PHP regular expression limit reached (pcre.recursion_limit)',
                PREG_BAD_UTF8_ERROR => 'Bad UTF8 passed to PCRE function',
                default => 'Unknown PCRE error calling PCRE function',
            };

            throw new \RuntimeException($message);
        }
    }
}
