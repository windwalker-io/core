<?php

declare(strict_types=1);

namespace Windwalker\Core\View;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Attributes\ViewMetadata;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Attributes\ViewPrepare;
use Windwalker\Core\Event\CoreEventAwareTrait;
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
use Windwalker\Filesystem\Path;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Language\Language;
use Windwalker\Utilities\Attributes\AttributesAccessor;
use Windwalker\Utilities\Attributes\Prop;
use Windwalker\Utilities\Iterator\PriorityQueue;
use Windwalker\Utilities\Options\OptionsResolverTrait;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\Wrapper\WrapperInterface;

use function Windwalker\has_attributes;

/**
 * The ViewModel class.
 */
class View implements EventAwareInterface, \ArrayAccess
{
    use OptionsResolverTrait;
    use CoreEventAwareTrait;

    protected bool $booted = false;

    protected string|array|null $layoutMap = null;

    protected string $layout = '';

    protected ?ResponseInterface $response = null;

    protected array $data = [];

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
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->addEventDealer($this->app);

        $this->booted = true;
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

    protected function findPrepareMethod(object $vm): \Closure
    {
        if ($vm instanceof ViewModelInterface || method_exists($vm, 'prepare')) {
            $prepare = $vm->prepare(...);
        } else {
            $prepare = AttributesAccessor::getFirstMemberWithAttribute(
                $vm,
                ViewPrepare::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );
        }

        return $prepare ?? throw new LogicException(
            sprintf(
                '%s must implement %s or has prepare() method.',
                $vm::class,
                ViewModelInterface::class
            )
        );
    }

    // public function getStyleScope(string $fullLayout): string
    // {
    //     $fullLayout = Path::relative(WINDWALKER_ROOT, $fullLayout);
    //
    //     $segments = explode('.', basename($fullLayout));
    //     $layoutName = array_shift($segments);
    //
    //     return $layoutName . '-' . substr(sha1($fullLayout), 0, 8);
    // }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('layout_var_name')
            ->default('layout')
            ->allowedTypes('string');

        $resolver->define('layout')
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

        $resolver->define('is_child')
            ->allowedTypes('bool')
            ->default(false);

        $resolver->define('vmAttr')
            ->allowedTypes(ViewModel::class, 'null')
            ->default(null);
    }

    public function renderLayout(string $layout, array $data = []): mixed
    {
        $options['layout'] = $layout;

        return $this->render($data, $options);
    }

    public function render(array $data = []): ResponseInterface
    {
        $this->boot();

        $data = [...$this->data, ...$data];

        $vm = $this->getViewModel();

        // $prepare = $this->findPrepareMethod($vm);

        if (EventSubscriber::isSubscriber($vm)) {
            $this->subscribe($vm);
        }

        $layout = $this->layout ?: $this->resolveLayout();

        // @event Before Prepare Data
        $event = $this->emit(
            new PrepareDataEvent(
                view: $this,
                viewModel: $vm,
                state: $this->getState(),
                layout: $layout,
                data: $data
            )
        );

        $vm = $event->viewModel;
        $data = $event->data;
        $this->layout = $layout = $event->layout;

        if ($data !== []) {
            $this->injectData($vm, $data);
        }

        // @event Before Render
        $event = $this->emit(
            new BeforeRenderEvent(
                response: $this->app->call(
                    $this->findPrepareMethod($vm),
                    [
                        $this->app,
                        $this,
                        'app' => $this->app,
                        'view' => $this,
                    ]
                ),
                view: $this,
                viewModel: $vm,
                state: $this->getState(),
                layout: $layout,
                data: $data
            )
        );

        $response = $this->handleVMResponse($event->response);

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if (is_array($response)) {
            $data = array_merge($this->rendererService->getGlobals(), $event->data, $response);

            $data['vm'] = $vm;

            $this->preparePaths($vm);

            if (!$this->layout) {
                throw new LogicException('View must provide at least 1 layout name.');
            }

            $this->prepareHtmlFrame($vm);

            $content = $this->rendererService->render($this->layout, $data, ['context' => $vm]);

            $response = $this->getResponse();
            $response->getBody()->write($content);
        } elseif (is_string($response)) {
            $content = $response;

            $this->prepareHtmlFrame($vm);

            $response = $this->getResponse();
            $response->getBody()->write($content);
        } else {
            $data['vm'] = $vm;

            $content = $response->getBody()->getContents();
        }

        // @event After Render
        $event = $this->emit(
            new AfterRenderEvent(
                content: $content,
                response: $response,
                view: $this,
                viewModel: $vm,
                state: $this->getState(),
                layout: $layout,
                data: $data
            )
        );

        return $event->response;
    }

    protected function handleVMResponse(mixed $data): array|string|ResponseInterface
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

        if (!is_array($data) && !is_string($data)) {
            throw new UnexpectedValueException(
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

        return (string) ($layout ?: $this->options['layout'] ?? '');
    }

    protected function injectData(object $vm, array $data): void
    {
        $ref = new ReflectionClass($vm);

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

    protected function prepareHtmlFrame(object $vm): void
    {
        $asset = $this->asset;
        $name = strtolower(ltrim($this->guessName($vm), '\\/'));
        $vmName = Path::clean($name, '/');

        if (!$this->isChild()) {
            $this->addBodyClass($vm::class);

            $methods = new ReflectionClass($vm)->getMethods();

            foreach ($methods as $method) {
                if (has_attributes($method, ViewMetadata::class)) {
                    $this->app->call(
                        $method->getClosure($vm),
                        [
                            self::class => $this,
                            'view' => $this,
                            ...$this->data,
                        ]
                    );
                }
            }
        }

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

        if ($asset->vite->isActive()) {
            foreach ($jsList as $name => $js) {
                $js = Path::stripExtension($js);

                if (is_numeric($name)) {
                    $asset->importByApp(
                        'view:' . md5("{$vmName}/$js"),
                        $this->app->isDebug() ? ['comment' => "/* {$vmName}/$js */"] : []
                    );
                } elseif ($name === $this->layout) {
                    foreach ((array) $js as $j) {
                        $asset->importByApp(
                            'view:' . md5("{$vmName}/$j"),
                            $this->app->isDebug() ? ['comment' => "/* {$vmName}/$j */"] : []
                        );
                    }
                }
            }
        } else {
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
                'isChild' => $this->isChild(),
            ]
        );
    }

    protected function addBodyClass(string $fullName): void
    {
        $classNames = [];

        $root = $this->app->config('asset.namespace_base');
        $fullName = ltrim(Str::removeLeft($fullName, $root, 'ascii'), '\\');

        $names = explode('\\', $fullName);
        $shortName = array_pop($names);
        $viewName = Str::removeRight($shortName, 'View');

        $stage = null;

        if (count($names) > 1) {
            $stage = $names[array_key_first($names)];
        }

        if ($stage) {
            $classNames[] = 'stage-' . $stage;
        }

        $module = implode('-', $names);
        $classNames[] = 'module-' . $module;
        $classNames[] = 'view-' . $viewName;
        $classNames[] = 'layout-' . str_replace('.', '-', $this->layout);

        $c = StrNormalize::toKebabCase(implode('##', $classNames));

        $this->htmlFrame->addBodyClass(str_replace('##', ' ', $c));
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

    protected function preparePaths(object $vm, string $ns = 'main'): void
    {
        // Prepare App view override
        // ROOT/views/{stage}/{view}
        $this->addPath($this->getAppTemplatePath($vm));

        // Prepare route override paths
        $route = $this->app->getMatchedRoute();

        if ($paths = $route?->getOption('layoutPaths')) {
            foreach ((array) $paths as $path) {
                $this->addPath($path);
            }
        }

        // Prepare Self view paths
        // `Module/{View}/views`
        $dir = $this->getTemplatePath($vm);

        if (class_exists(Language::class)) {
            $langService = $this->app->service(LangService::class);

            $langDir = $dir . '/' . $langService->getLocale();

            if (is_dir($dir)) {
                $this->addPath($langDir, PriorityQueue::BELOW_NORMAL, $ns);
            }

            $fallbackDir = $dir . '/' . $langService->getLocale();

            if ($langDir !== $fallbackDir && is_dir($dir)) {
                $this->addPath($fallbackDir, PriorityQueue::BELOW_NORMAL, $ns);
            }
        }

        if (is_dir($dir)) {
            $this->addPath($dir, PriorityQueue::BELOW_NORMAL, $ns);
        }
    }

    public function addViewPath(string|object $view, int $priority = PriorityQueue::LOW, string $ns = 'main'): static
    {
        if (is_object($view)) {
            $view = $view::class;
        }

        $ref = new ReflectionClass($view);

        $this->addPath(dirname($ref->getFileName()) . '/views', $priority, $ns);

        return $this;
    }

    public function addParentViewPath(int $priority = PriorityQueue::LOW, string $ns = 'main'): static
    {
        $this->addViewPath(get_parent_class($this->getViewModel()), $priority, $ns);

        return $this;
    }

    protected function getPageAttribute(): ?ViewModel
    {
        return $this->options['vmAttr'];
    }

    public function guessName(object $vm): string
    {
        $root = $this->app->config('asset.namespace_base');

        $ref = new ReflectionClass($vm);
        $ns = $ref->getNamespaceName();

        if (str_starts_with($ns, $root)) {
            return Str::removeLeft($ns, $root, 'ascii');
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

    protected function getAppTemplatePath(object $vm): string
    {
        $name = $this->guessName($vm);

        return $this->app->path('@views/' . strtolower($name));
    }

    protected function getTemplatePath(object $vm): string
    {
        $ref = new ReflectionClass($vm);

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

    public function isChild(): bool
    {
        return $this->options['is_child'];
    }

    public function set(string $id, mixed $value): static
    {
        $this->data[$id] = $value;

        return $this;
    }

    public function bind(string $id, mixed $value): static
    {
        return $this->set($id, $value);
    }

    public function get(string $id): mixed
    {
        return $this->data[$id] ?? null;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
