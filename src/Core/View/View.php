<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\View;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\Event\AfterRenderEvent;
use Windwalker\Core\View\Event\BeforeRenderEvent;
use Windwalker\Core\View\Event\PrepareDataEvent;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Filesystem\Path;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Language\Language;
use Windwalker\Utilities\Attributes\Prop;
use Windwalker\Utilities\Iterator\PriorityQueue;
use Windwalker\Utilities\Options\OptionsResolverTrait;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\Wrapper\WrapperInterface;

/**
 * The ViewModel class.
 */
class View implements EventAwareInterface
{
    use OptionsResolverTrait;
    use EventAwareTrait;

    protected string|array|null $layoutMap = null;

    protected string $layout = '';

    protected ?ResponseInterface $response = null;

    /**
     * View constructor.
     *
     * @param  object           $viewModel
     * @param  AppContext       $app
     * @param  RendererService  $rendererService
     * @param  array            $options
     */
    public function __construct(
        protected object $viewModel,
        protected AppContext $app,
        protected RendererService $rendererService,
        protected HtmlFrame $htmlFrame,
        protected AssetService $asset,
        array $options = []
    ) {
        $this->resolveOptions($options, [$this, 'configureOptions']);

        $this->addEventDealer($this->app);
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param  string  $layout
     *
     * @return  static  Return self to support chaining.
     */
    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('layout_var_name')
            ->default('layout')
            ->allowedTypes('string');

        $resolver->define('module')
            ->allowedTypes(ModuleInterface::class, 'null')
            ->default(null);

        $resolver->define('modules')
            ->allowedTypes('null', 'array')
            ->default([]);

        $resolver->define('css')
            ->allowedTypes('null', 'array')
            ->default([]);

        $resolver->define('js')
            ->allowedTypes('null', 'array')
            ->default([]);

        $resolver->define('headers')
            ->allowedTypes('null', 'array')
            ->default([]);

        $resolver->define('vmAttr')
            ->allowedTypes(ViewModel::class, 'null')
            ->default(null);
    }

    public function render(array $data = []): mixed
    {
        $vm = $this->getViewModel();

        if (!$vm instanceof ViewModelInterface && method_exists($vm, 'prepare')) {
            throw new \LogicException(
                sprintf(
                    '%s must implement %s or has prepare() method.',
                    $vm::class,
                    ViewModelInterface::class
                )
            );
        }

        if (AttributesAccessor::getFirstAttribute($vm, EventSubscriber::class)) {
            $this->subscribe($vm);
        }

        $layout = $this->resolveLayout();

        // @event Before Prepare Data
        $event = $this->emit(
            PrepareDataEvent::class,
            [
                'view' => $this,
                'viewModel' => $vm,
                'data' => $data,
                'layout' => $layout,
                'state' => $this->getState(),
            ]
        );

        $vm = $event->getViewModel();
        $data = $event->getData();
        $this->layout = $layout = $event->getLayout();

        if ($data !== []) {
            $this->injectData($vm, $data);
        }

        // @event Before Render
        $event = $this->emit(
            BeforeRenderEvent::class,
            [
                'view' => $this,
                'viewModel' => $vm,
                'data' => $data,
                'layout' => $layout,
                'state' => $this->getState(),
                'response' => $vm->prepare($this->app, $this),
            ]
        );

        $response = $this->handleVMResponse($event->getResponse());

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if (is_array($response)) {
            $data = array_merge($this->rendererService->getGlobals(), $event->getData(), $response);

            $data['vm'] = $vm;

            $route = $this->app->getMatchedRoute();

            if ($paths = $route?->getOption('layoutPaths')) {
                foreach ((array) $paths as $path) {
                    $this->addPath($path);
                }
            }

            $this->preparePaths($this->getTemplatePath($vm));

            if (!$this->layout) {
                throw new \LogicException('View must provide at least 1 layout name.');
            }

            $this->prepareHtmlFrame($vm);

            $content = $this->rendererService->render($this->layout, $data, ['context' => $vm]);

            $response = $this->getResponse();
            $response->getBody()->write($content);
        } else {
            $data['vm'] = $vm;

            $content = $response->getBody()->getContents();
        }

        // @event After Render
        $event = $this->emit(
            AfterRenderEvent::class,
            [
                'view' => $this,
                'viewModel' => $vm,
                'data' => $data,
                'layout' => $layout,
                'content' => $content,
                'response' => $response,
            ]
        );

        return $event->getResponse();
    }

    protected function handleVMResponse(mixed $data): array|ResponseInterface
    {
        if ($data instanceof WrapperInterface) {
            $data = $data($this);
        }

        if ($data instanceof ResponseInterface) {
            return $data;
        }

        if ($data instanceof RouteUri) {
            return $data->toResponse();
        }

        if ($data instanceof UriInterface) {
            return new RedirectResponse($data);
        }

        if (!is_array($data)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'ViewModel return value not support for: %s',
                    get_debug_type($data)
                )
            );
        }

        return $data;
    }

    protected function getState(): AppState
    {
        return $this->getModule()?->getState() ?? $this->app->getState();
    }

    protected function getModule(): ?ModuleInterface
    {
        return $this->options['module'];
    }

    public function resolveLayout(): string
    {
        $vm = $this->getViewModel();

        // Get global layout overrides
        $layouts = $this->app->config('di.layouts') ?? [];
        $layout = $layouts[$vm::class] ?? $this->getLayoutMap();

        if (is_array($layout)) {
            $varName = $this->options['layout_var_name'];
            $layoutType = $this->app->input($varName) ?: 'default';

            if (isset($layout[$layoutType])) {
                return $layout[$layoutType];
            }

            if (
                in_array($layoutType, $layout, true)
                && !is_string(array_search($layoutType, $layout, true))
            ) {
                return $layoutType;
            }

            return $layout['default'] ?? '';
        }

        return (string) $layout;
    }

    protected function injectData(object $vm, array $data): void
    {
        $ref = new \ReflectionClass($vm);

        foreach ($ref->getProperties() as $property) {
            AttributesAccessor::runAttributeIfExists(
                $property,
                Prop::class,
                function ($attr) use ($data, $vm, $property) {
                    if (array_key_exists($property->getName(), $data)) {
                        $property->setValue($vm, $data[$property->getName()]);
                    }
                }
            );
        }
    }

    protected function prepareHtmlFrame(ViewModelInterface $vm): void
    {
        $asset = $this->asset;
        $name = strtolower(ltrim($this->guessName($vm), '\\/'));
        $vmName = Path::clean($name, '/');
        $this->addBodyClass($vm::class);

        $cssList = $this->getOption('css');
        $jsList = $this->getOption('js');
        $modules = $this->getOption('modules');

        foreach ($cssList as $name => $css) {
            if (is_numeric($name)) {
                $asset->css("@view/{$vmName}/{$css}");
            } elseif ($name === $this->layout) {
                foreach ((array) $css as $c) {
                    $asset->css("@view/{$vmName}/{$c}");
                }
            }
        }

        foreach ($jsList as $name => $js) {
            if (is_numeric($name)) {
                $asset->js("@view/{$vmName}/{$js}");
            } elseif ($name === $this->layout) {
                foreach ((array) $js as $j) {
                    $asset->js("@view/{$vmName}/{$j}");
                }
            }
        }

        foreach ($modules as $name => $js) {
            if (is_numeric($name)) {
                $asset->module("@view/{$vmName}/{$js}");
            } elseif ($name === $this->layout) {
                foreach ((array) $js as $j) {
                    $asset->module("@view/{$vmName}/{$j}");
                }
            }
        }

        $this->app->getState()->set(
            'view',
            [
                'name' => $vmName,
                'layout' => $this->layout,
                'css' => $cssList,
                'js' => $jsList,
                'modules' => $modules,
                'className' => $vm::class,
            ]
        );
    }

    protected function addBodyClass(string $fullName): void
    {
        $root = $this->app->config('asset.namespace_base');
        $fullName = ltrim(Str::removeLeft($fullName, $root), '\\');

        $names = explode('\\', $fullName);
        $shortName = array_pop($names);
        $viewName = Str::removeRight($shortName, 'View');

        $stage = null;

        if (\Windwalker\count($names) > 1) {
            $stage = $names[array_key_first($names)];
        }

        if ($stage) {
            $this->htmlFrame->addBodyClass('stage-' . StrNormalize::toKebabCase($stage));
        }

        $module = StrNormalize::toKebabCase(implode('-', $names));

        $this->htmlFrame->addBodyClass('module-' . $module);
        $this->htmlFrame->addBodyClass('view-' . StrNormalize::toKebabCase($viewName));
        $this->htmlFrame->addBodyClass('layout-' . $this->layout);
    }

    /**
     * @return string|array|null
     */
    public function getLayoutMap(): string|array|null
    {
        return $this->layoutMap;
    }

    /**
     * @param  string|array|null  $layoutMap
     *
     * @return  static  Return self to support chaining.
     */
    public function setLayoutMap(string|array|null $layoutMap): static
    {
        $this->layoutMap = $layoutMap;

        return $this;
    }

    /**
     * @return object
     */
    public function getViewModel(): object
    {
        return $this->viewModel;
    }

    /**
     * @param  object  $viewModel
     *
     * @return  static  Return self to support chaining.
     */
    public function setViewModel(object $viewModel): static
    {
        $this->viewModel = $viewModel;

        return $this;
    }

    public function addPath(string $path, int $priority = 100, string $ns = 'main'): static
    {
        $this->rendererService->addPath($path, $priority, $ns);

        return $this;
    }

    protected function preparePaths(string $dir, string $ns = 'main'): void
    {
        if (is_dir($dir)) {
            $this->addPath($dir, PriorityQueue::LOW, $ns);
        }

        if (class_exists(Language::class)) {
            $langService = $this->app->service(LangService::class);

            $langDir = $dir . '/' . $langService->getLocale();

            if (is_dir($dir)) {
                $this->addPath($langDir, PriorityQueue::BELOW_NORMAL, $ns);
            }

            $fallbackDir = $dir . '/' . $langService->getLocale();

            if (is_dir($dir)) {
                $this->addPath($fallbackDir, PriorityQueue::BELOW_NORMAL, $ns);
            }
        }
    }

    public function addViewPath(string|object $view, int $priority = 100, string $ns = 'main'): static
    {
        if (is_object($view)) {
            $view = $view::class;
        }

        $ref = new \ReflectionClass($view);

        $this->addPath(dirname($ref->getFileName()) . '/views', $priority, $ns);

        return $this;
    }

    public function addParentViewPath(int $priority = 100, string $ns = 'main'): static
    {
        $this->addViewPath(get_parent_class($this->getViewModel()), $priority, $ns);

        return $this;
    }

    protected function getPageAttribute(): ?ViewModel
    {
        return $this->options['vmAttr'];
    }

    public function guessName(ViewModelInterface $vm): string
    {
        $root = $this->app->config('asset.namespace_base');

        $ref = new \ReflectionClass($vm);
        $ns = $ref->getNamespaceName();

        if (str_starts_with($ns, $root)) {
            return Str::removeLeft($ns, $root);
        }

        return substr($ns, strpos($ns, 'Module') + 7);
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

        if ($this->getOption('headers')) {
            foreach ($this->getOption('headers') as $header => $values) {
                foreach ((array) $values as $value) {
                    $res = $res->withAddedHeader($header, $value);
                }
            }
        }

        return $res;
    }

    /**
     * @return HtmlFrame
     */
    public function getHtmlFrame(): HtmlFrame
    {
        return $this->htmlFrame;
    }

    public function setTitle(string $title): static
    {
        $this->htmlFrame->setTitle($title);

        return $this;
    }

    public function configureHtmlFrame(callable $handler): static
    {
        $handler($this->htmlFrame);

        return $this;
    }

    protected function getAppTemplatePath(ViewModelInterface $vm): string
    {
        $name = $this->guessName($vm);

        return $this->app->path('@source/Module/' . $name . '/views');
    }

    protected function getTemplatePath(object $vm): string
    {
        $ref = new \ReflectionClass($vm);

        return dirname($ref->getFileName()) . '/views';
    }

    public function getPaths(): PriorityQueue
    {
        return $this->rendererService->getPaths();
    }

    public function dumpPaths(): array
    {
        return $this->rendererService->dumpPaths();
    }
}
