<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Unicorn\Script\UnicornScript;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Asset\Event\AssetBeforeRender;
use Windwalker\Core\Security\CspNonceService;
use Windwalker\DI\Container;

/**
 * The SimpleCspMiddleware class.
 */
class SimpleCspMiddleware implements MiddlewareInterface
{
    protected string $nonce = '';

    public const FRAME_SRC = 1 << 0;

    public const IMG_SRC = 1 << 1;

    public const MEDIA_SRC = 1 << 2;

    public const OBJECT_SRC = 1 << 3;

    public const SCRIPT_SRC = 1 << 4;

    public const STYLE_SRC = 1 << 5;

    public const FONT_SRC = 1 << 6;

    public const DEFAULT_PRESET = self::SCRIPT_SRC | self::STYLE_SRC | self::IMG_SRC | self::FONT_SRC;

    /**
     * Use the CSP Builder, please see: https://github.com/paragonie/csp-builder
     *
     * @param  AssetService     $asset
     * @param  CspNonceService  $cspNonceService
     * @param  Container        $container
     * @param  bool             $enabled
     * @param  int|\Closure     $cspRules
     * @param  \Closure|null    $extendCsp
     */
    public function __construct(
        protected AssetService $asset,
        protected CspNonceService $cspNonceService,
        protected Container $container,
        protected bool $enabled = true,
        protected int|\Closure $cspRules = self::DEFAULT_PRESET,
        protected ?\Closure $extendCsp = null
    ) {
        //
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!class_exists(CSPBuilder::class)) {
            throw new \DomainException(
                'Please install `paragonie/csp-builder` first.'
            );
        }

        if ($this->enabled) {
            $uniScript = $this->container->get(UnicornScript::class);
            $uniScript->exposeCspNonce();

            $nonce = $this->nonce = $this->cspNonceService->getNonce();

            $this->asset->on(
                AssetBeforeRender::class,
                function (AssetBeforeRender $event) use ($nonce) {
                    $links = &$event->getLinks();

                    foreach ($links as $i => $link) {
                        $links[$i] = $link->withAttribute('nonce', $nonce);
                    }

                    $attrs = &$event->getInternalAttrs();
                    $attrs['nonce'] = $nonce;
                }
            );

            $this->asset->getImportMap()->setCspNonce($nonce);
        }

        $response = $handler->handle($request);

        if ($this->enabled) {
            $cspBuilder = new CSPBuilder();

            if ($this->cspRules instanceof \Closure) {
                $cspBuilder = $this->container->call(
                    $this->cspRules,
                    [
                        $cspBuilder,
                        'csp' => $cspBuilder,
                        CSPBuilder::class => $cspBuilder,
                    ]
                ) ?? $cspBuilder;
            } else {
                $nonce = $this->nonce;
                $cspBuilder->addDirective('default-src', ['self' => true]);

                if ($this->cspRules & static::IMG_SRC) {
                    $cspBuilder->addDirective('img-src', []);
                    $cspBuilder->addSource('img-src', '*');
                    $cspBuilder->setDataAllowed('img-src', true);
                    $cspBuilder->setBlobAllowed('img-src', true);
                }

                if ($this->cspRules & static::FONT_SRC) {
                    $cspBuilder->addDirective('font-src', []);
                    $cspBuilder->addSource('font-src', '*');
                    $cspBuilder->setDataAllowed('font-src', true);
                    $cspBuilder->setBlobAllowed('font-src', true);
                    $cspBuilder->nonce('font-src', $nonce);
                }

                if ($this->cspRules & static::MEDIA_SRC) {
                    $cspBuilder->addDirective('media-src', []);
                    $cspBuilder->nonce('media-src', $nonce);
                }

                if ($this->cspRules & static::OBJECT_SRC) {
                    $cspBuilder->addDirective('object-src', []);
                    $cspBuilder->nonce('object-src', $nonce);
                }

                if ($this->cspRules & static::SCRIPT_SRC) {
                    $cspBuilder->addDirective('script-src-elem', []);
                    $cspBuilder->setAllowUnsafeInline('script-src-elem', true);
                    $cspBuilder->setStrictDynamic('script-src-elem', true);
                    $cspBuilder->nonce('script-src-elem', $nonce);

                    $cspBuilder->addDirective('script-src-attr', []);
                    $cspBuilder->setAllowUnsafeInline('script-src-attr', true);
                }

                if ($this->cspRules & static::STYLE_SRC) {
                    $cspBuilder->addDirective('style-src-elem', []);
                    $cspBuilder->setAllowUnsafeInline('style-src-elem', true);
                    $cspBuilder->setStrictDynamic('style-src-elem', true);
                    $cspBuilder->nonce('style-src-elem', $nonce);

                    $cspBuilder->addDirective('style-src-attr', []);
                    $cspBuilder->setAllowUnsafeInline('style-src-attr', true);
                }
            }

            if ($this->extendCsp) {
                $cspBuilder = $this->container->call(
                    $this->extendCsp,
                    [
                        $cspBuilder,
                        'csp' => $cspBuilder,
                        CSPBuilder::class => $cspBuilder,
                    ]
                ) ?? $cspBuilder;
            }

            $response = $response->withHeader('Content-Security-Policy', $cspBuilder->getCompiledHeader());
        }

        return $response;
    }
}
