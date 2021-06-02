<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\View\Event\BeforeRenderEvent;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Path;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Utilities\Str;

/**
 * The HtmlPage class.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewModel implements ContainerAttributeInterface
{
    public array $css;

    public array $js;

    protected ?ResponseInterface $response = null;

    /**
     * View constructor.
     *
     * @param  string|null        $module
     * @param  string|array|null  $layout
     * @param  array|string       $css
     * @param  array|string       $js
     * @param  array              $modules
     * @param  array              $options
     */
    public function __construct(
        public ?string $module = null,
        public string|array|null $layout = null,
        public array $headers = [],
        array|string $css = [],
        array|string $js = [],
        public array $modules = [],
        public array $options = []
    ) {
        $this->css = (array) $css;
        $this->js  = (array) $js;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        $container = $handler->getContainer();
        $container->share(static::class, $this);
        $container->share('vm.self', $this);

        return function (...$args) use ($container, $handler) {
            $options = [
                'vmAttr' => $this,
            ];

            if ($this->module) {
                /** @var ModuleInterface $module */
                $options['module'] = $module = $container->newInstance($this->module);

                $container->share($this->module, $module)
                    ->alias(ModuleInterface::class, $this->module);
            }

            $viewModel = $handler(...$args);

            $vm = $this->registerEvents(
                $container->newInstance(
                    View::class,
                    compact('viewModel', 'options')
                )
                    ->setLayoutMap($this->layout),
                $viewModel,
                $container
            );

            $container->remove(static::class);
            $container->remove('vm.self');

            if ($this->module) {
                $container->remove($this->module)
                    ->removeAlias(ModuleInterface::class);
            }

            return $vm;
        };
    }

    protected function registerEvents(View $view, ViewModelInterface $vm, Container $container): View
    {
        $view->on(
            BeforeRenderEvent::class,
            function (BeforeRenderEvent $event) use ($vm, $container) {
                $asset  = $container->resolve(AssetService::class);
                $name   = $this->guessName($vm, $container);
                $vmName = Path::clean(strtolower(ltrim($name, '\\/')), '/');

                foreach ($this->css as $name => $css) {
                    $path = '@view/' . $css;

                    $asset->css($path);
                }

                foreach ($this->js as $name => $js) {
                    $path = '@view/' . $vmName . '/' . $js;

                    $asset->js($path);
                }

                foreach ($this->modules as $name => $js) {
                    $path = '@view/' . $vmName . '/' . $js;

                    if (!str_ends_with($js, '/')) {
                        $asset->module($path);
                    }

                    if (!is_numeric($name)) {
                        $asset->importMap($name, $path);
                    }
                }

                $layout    = $event->getLayout();
                $className = str_replace('/', '-', $vmName);

                $htmlFrame = $container->get(HtmlFrame::class);
                $htmlFrame->addBodyClass("view-$className layout-$layout");
            }
        );

        return $view;
    }

    /**
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    public function guessName(ViewModelInterface $vm, Container $container): string
    {
        $root = $container->getParam('asset.namespace_base');

        $ref = new \ReflectionClass($vm);
        $ns  = $ref->getNamespaceName();

        return Str::removeLeft($ns, $root);
    }

    public function header(string $name, string|array $value): static
    {
        // Init response
        $this->getResponse();

        foreach ((array) $value as $v) {
            $this->response = $this->response->withAddedHeader($name, $v);
        }

        return $this;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response ??= $this->createResponse();
    }

    public function createResponse(): ResponseInterface
    {
        $res = new HtmlResponse();

        if ($this->headers !== []) {
            foreach ($this->headers as $header) {
                foreach ((array) $header as $value) {
                    $res = $res->withAddedHeader($header, $value);
                }
            }
        }

        return $res;
    }
}
