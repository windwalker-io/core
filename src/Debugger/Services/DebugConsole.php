<?php

declare(strict_types=1);

namespace Windwalker\Debugger\Services;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Security\CspNonceService;
use Windwalker\Data\Collection;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Stream\Stream;

/**
 * The DebugConsole class.
 */
class DebugConsole
{
    public static bool $disabled = false;

    /**
     * DebugConsole constructor.
     */
    public function __construct(
        protected RendererService $rendererService,
        protected CspNonceService $cspNonceService,
        protected AssetService $assetService
    ) {
    }

    public static function disable(): void
    {
        static::$disabled = true;
    }

    public function pushToPage(
        Collection $collector,
        OutputInterface $output,
        ?ResponseInterface $response = null
    ): void {
        if (static::$disabled) {
            return;
        }

        $tmpl = 'console';

        if ($response) {
            $ct = $response->getHeaderLine('content-type');

            if (str_contains($ct, 'text/html') || str_contains($ct, 'text/plain')) {
                $console = $this->renderConsole($tmpl, $collector);
                $output->write($console);
            }

            return;
        }

        if ($this->isHtmlPage()) {
            $output->write($this->renderConsole($tmpl, $collector));
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
                'js' => $js,
                'nonce' => $this->cspNonceService->getNonce()
            ]
        );
    }
}
