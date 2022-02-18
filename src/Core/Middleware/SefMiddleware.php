<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
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

/**
 * The SefMiddleware class.
 */
class SefMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected AppContext $app,
        protected bool $enabled = true,
        protected ?string $baseHref = null,
        protected ?string $baseSrc = null,
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

            $stream = new Stream('php://memory', Stream::MODE_READ_WRITE_FROM_BEGIN);
            $stream->write($newBody);

            $response = $response->withBody($stream);
        }

        return $response;
    }

    /**
     * replaceRoutes
     *
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
        if (str_contains($buffer, '#')) {
            $fullPath = (new Uri($uri->full()))->toString(Uri::PATH | Uri::QUERY);

            $regex  = '#\shref="\#(?!/|\')([^"]*)"#m';
            $buffer = preg_replace($regex, ' href="' . $fullPath . '#$1"', $buffer);
            $this->checkBuffer($buffer);
        }

        // Check for all unknown protocals
        // (a protocol must contain at least one alpahnumeric character followed by a ":").
        $protocols  = '[a-zA-Z0-9\-]+:';
        $attributes = [
            'href="' => $baseHref,
            'src="' => $baseSrc,
            'poster="' => $baseSrc
        ];

        foreach ($attributes as $attribute => $base) {
            if (str_contains($buffer, $attribute)) {
                $regex  = '#\s' . $attribute . '(?!/|' . $protocols . '|\#|\')([^"]*)"#m';
                $buffer = preg_replace($regex, ' ' . $attribute . '"' . $base . '$1$2"', $buffer);
                $this->checkBuffer($buffer);
            }
        }

        if (str_contains($buffer, 'srcset=')) {
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
        if (str_contains($buffer, 'window.open(')) {
            $regex  = '#onclick="window.open\(\'(?!/|' . $protocols . '|\#)([^/]+[^\']*?\')#m';
            $buffer = preg_replace($regex, 'onclick="window.open(\'' . $baseHref . '$1', $buffer);
            $this->checkBuffer($buffer);
        }
        // Replace all unknown protocols in onmouseover and onmouseout attributes.
        $attributes = ['onmouseover=', 'onmouseout='];

        foreach ($attributes as $attribute) {
            if (str_contains($buffer, $attribute)) {
                $regex  = '#' . $attribute . '"this.src=([\']+)(?!/|' . $protocols . '|\#|\')([^"]+)"#m';
                $buffer = preg_replace($regex, $attribute . '"this.src=$1' . $baseSrc . '$2"', $buffer);
                $this->checkBuffer($buffer);
            }
        }

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

        return $buffer;
    }

    /**
     * checkBuffer
     *
     * @param string $buffer
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
