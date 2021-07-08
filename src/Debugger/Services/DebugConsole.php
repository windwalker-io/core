<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Debugger\Services;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Data\Collection;
use Windwalker\Debugger\DebuggerPackage;
use Windwalker\Utilities\Str;

/**
 * The DebugConsole class.
 */
class DebugConsole
{
    /**
     * DebugConsole constructor.
     */
    public function __construct(
        protected RendererService $rendererService,
        protected AssetService $assetService
    ) {
    }

    public function pushToPage(Collection $collector, ?ResponseInterface $response = null): void
    {
        $tmpl = 'console';

        if ($response) {
            $ct = $response->getHeaderLine('content-type');

            if (str_contains($ct, 'text/html') || str_contains($ct, 'text/plain')) {
                $console = $this->renderConsole($tmpl, $collector);

                $body = $response->getBody();
                $body->write($console);
            }
            return;
        }

        if ($this->isHtmlPage()) {
            echo $this->renderConsole($tmpl, $collector);
        }
    }

    protected function isHtmlPage(): bool
    {
        if (!function_exists('header_list')) {
            return false;
        }

        $headers = headers_list();

        foreach ($headers as $header) {
            $header = strtolower($header);

            if (str_starts_with($header, 'content-type') && str_contains($header, 'text/html')) {
                return true;
            }
        }

        return false;
    }

    public function renderConsole(string $tmpl, Collection $collector): string
    {
        $css = $this->assetService->appendVersion(
            $this->assetService->handleUri('@core/debugger-console.css')
        );
        $js = $this->assetService->appendVersion(
            $this->assetService->handleUri('@core/debugger-console.js')
        );

        return $this->rendererService->render(
            $tmpl,
            [
                'collector' => $collector,
                'css' => $css,
                'js' => $js
            ]
        );
    }
}
