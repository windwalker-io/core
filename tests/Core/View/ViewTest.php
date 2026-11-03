<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionProperty;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetLink;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Asset\ViteResolver;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;
use Windwalker\Event\EventEmitter;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Tests\Core\View\Stub\AnnotatedViewModelStub;
use Windwalker\Tests\Core\View\Stub\AttributeBasedViewModelStub;
use Windwalker\Tests\Core\View\Stub\Module\Front\Home\DummyViewModel;
use Windwalker\Tests\Core\View\Stub\SubscriberViewModelStub;
use Windwalker\Tests\Core\View\Stub\ViewModelStub;
use Windwalker\Tests\Core\View\Stub\ViewModelStubChild;
use Windwalker\Uri\Uri;
use Windwalker\Utilities\Iterator\PriorityQueue;
use Windwalker\Utilities\Wrapper\WrapperInterface;

/**
 * Tier-1 unit tests for View, using doubles for every collaborator
 * (AppContext, RendererService, HtmlFrame, AssetService). No host app,
 * container wiring, or database is needed to exercise this class.
 */
#[CoversClass(View::class)]
class ViewTest extends TestCase
{
    /**
     * @param  bool  $mockRenderer  Pass true only when the test asserts call arguments via
     *                              `$renderer->expects(...)`; otherwise a plain stub is used
     *                              to avoid PHPUnit's "mock without expectations" notice.
     *
     * @return  array{0: View, 1: AppContext, 2: RendererService&\PHPUnit\Framework\MockObject\MockObject, 3: HtmlFrame, 4: AssetService}
     */
    private function createView(object $viewModel, array $options = [], bool $mockRenderer = false): array
    {
        $app = $this->createStub(AppContext::class);
        $app->method('getEventDispatcher')->willReturn(new EventEmitter());
        $app->method('getState')->willReturn(new AppState(new Container()));

        $renderer = $mockRenderer
            ? $this->createMock(RendererService::class)
            : $this->createStub(RendererService::class);
        $htmlFrame = $this->createStub(HtmlFrame::class);

        $asset = $this->createStub(AssetService::class);
        $vite = $this->createStub(ViteResolver::class);
        $vite->method('isActive')->willReturn(false);
        new ReflectionProperty(AssetService::class, 'vite')->setValue($asset, $vite);

        $view = new View($viewModel, $app, $renderer, $htmlFrame, $asset, $options);

        return [$view, $app, $renderer, $htmlFrame, $asset];
    }

    /**
     * Returns a duck-typed getLocale()/getFallback() provider. View::preparePaths()
     * only calls these two methods on whatever AppContext::service(LangService::class)
     * returns, so a real LangService/Language instance is unnecessary here.
     * Defaults keep locale === fallback so existing tests do not add a fallback path.
     */
    private function createLangServiceStub(string $locale = 'en', string $fallback = 'en'): object
    {
        return new class ($locale, $fallback) {
            public function __construct(private string $locale, private string $fallback)
            {
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            public function getFallback(): string
            {
                return $this->fallback;
            }
        };
    }

    #[Test]
    public function constructorAppliesDefaultOptions(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        self::assertFalse($view->isChild());
        self::assertSame([], $view->getData());
        self::assertSame('', $view->getLayout());
        self::assertNull($view->getLayoutMap());
    }

    #[Test]
    public function getLayoutAndSetLayoutRoundTrip(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        self::assertSame($view, $view->setLayout('site.default'));
        self::assertSame('site.default', $view->getLayout());
    }

    #[Test]
    public function getViewModelAndSetViewModelRoundTrip(): void
    {
        [$view] = $this->createView(new ViewModelStub());
        $newVm = new ViewModelStub();

        self::assertSame($view, $view->setViewModel($newVm));
        self::assertSame($newVm, $view->getViewModel());
    }

    #[Test]
    public function getLayoutMapAndSetLayoutMapRoundTrip(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        self::assertSame($view, $view->setLayoutMap(['default' => 'site.default']));
        self::assertSame(['default' => 'site.default'], $view->getLayoutMap());
    }

    #[Test]
    public function isChildReflectsIsChildOption(): void
    {
        [$view] = $this->createView(new ViewModelStub(), ['is_child' => true]);

        self::assertTrue($view->isChild());
    }

    #[Test]
    public function dataAccessorsSetBindGetWork(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        self::assertSame($view, $view->set('foo', 'bar'));
        self::assertSame($view, $view->bind('baz', 'qux'));

        self::assertSame('bar', $view->get('foo'));
        self::assertSame('qux', $view->get('baz'));
        self::assertNull($view->get('missing'));
        self::assertSame(['foo' => 'bar', 'baz' => 'qux'], $view->getData());
    }

    #[Test]
    public function setDataReplacesAllData(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        $view->set('foo', 'bar');

        self::assertSame($view, $view->setData(['new' => 1]));
        self::assertSame(['new' => 1], $view->getData());
    }

    #[Test]
    public function arrayAccessDelegatesToData(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        $view['foo'] = 'bar';

        self::assertTrue(isset($view['foo']));
        self::assertSame('bar', $view['foo']);
        self::assertNull($view['missing']);
        self::assertFalse(isset($view['missing']));

        unset($view['foo']);

        self::assertFalse(isset($view['foo']));
    }

    #[Test]
    public function headerAddsHeaderToResponse(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        self::assertSame($view, $view->header('X-Foo', ['one', 'two']));

        $response = $view->getResponse();

        self::assertSame(['one', 'two'], $response->getHeader('X-Foo'));
    }

    #[Test]
    public function getResponseCreatesAndMemoizesDefaultResponse(): void
    {
        [$view] = $this->createView(new ViewModelStub());

        $response = $view->getResponse();

        self::assertInstanceOf(HtmlResponse::class, $response);
        self::assertSame($response, $view->getResponse());
    }

    #[Test]
    public function createResponseAddsConfiguredHeaders(): void
    {
        [$view] = $this->createView(
            new ViewModelStub(),
            ['headers' => ['X-Foo' => ['one', 'two']]]
        );

        $response = $view->createResponse();

        self::assertSame(['one', 'two'], $response->getHeader('X-Foo'));
    }

    #[Test]
    public function getHtmlFrameReturnsInjectedInstance(): void
    {
        [$view, , , $htmlFrame] = $this->createView(new ViewModelStub());

        self::assertSame($htmlFrame, $view->getHtmlFrame());
    }

    #[Test]
    public function setTitleDelegatesToHtmlFrame(): void
    {
        $htmlFrame = $this->createMock(HtmlFrame::class);
        $htmlFrame->expects($this->once())->method('setTitle')->with('Hello');

        $app = $this->createStub(AppContext::class);
        $renderer = $this->createStub(RendererService::class);
        $asset = $this->createStub(AssetService::class);

        $view = new View(new ViewModelStub(), $app, $renderer, $htmlFrame, $asset);

        self::assertSame($view, $view->setTitle('Hello'));
    }

    #[Test]
    public function configureHtmlFrameInvokesHandlerWithHtmlFrame(): void
    {
        [$view, , , $htmlFrame] = $this->createView(new ViewModelStub());

        $received = null;

        self::assertSame(
            $view,
            $view->configureHtmlFrame(
                function ($frame) use (&$received) {
                    $received = $frame;
                }
            )
        );

        self::assertSame($htmlFrame, $received);
    }

    #[Test]
    public function resolveLayoutReturnsPlainLayoutOptionWhenNoLayoutMapOrOverride(): void
    {
        [$view, $app] = $this->createView(new ViewModelStub(), ['layout' => 'site.default']);
        $app->method('config')->willReturn('');

        self::assertSame('site.default', $view->resolveLayout());
    }

    #[Test]
    public function resolveLayoutUsesInputVarNameToPickFromLayoutMap(): void
    {
        [$view, $app] = $this->createView(new ViewModelStub());
        $app->method('config')->willReturn('');
        $app->method('input')->willReturnCallback(
            fn(string $name) => $name === 'layout' ? 'edit' : null
        );

        $view->setLayoutMap(['default' => 'site.default', 'edit' => 'site.edit']);

        self::assertSame('site.edit', $view->resolveLayout());
    }

    #[Test]
    public function resolveLayoutFallsBackToDefaultKeyWhenLayoutTypeNotFound(): void
    {
        [$view, $app] = $this->createView(new ViewModelStub());
        $app->method('config')->willReturn('');
        $app->method('input')->willReturnCallback(
            fn(string $name) => $name === 'layout' ? 'unknown' : null
        );

        $view->setLayoutMap(['default' => 'site.default']);

        self::assertSame('site.default', $view->resolveLayout());
    }

    #[Test]
    public function resolveLayoutReturnsLayoutTypeItselfWhenMatchingNumericKeyedValue(): void
    {
        [$view, $app] = $this->createView(new ViewModelStub());
        $app->method('config')->willReturn('');
        $app->method('input')->willReturnCallback(
            fn(string $name) => $name === 'layout' ? 'site.raw' : null
        );

        $view->setLayoutMap(['default' => 'site.default', 'site.raw']);

        self::assertSame('site.raw', $view->resolveLayout());
    }

    #[Test]
    public function resolveLayoutUsesDiLayoutsConfigOverride(): void
    {
        $vm = new ViewModelStub();

        [$view, $app] = $this->createView($vm, ['layout' => 'site.default']);
        $app->method('config')->willReturnCallback(
            fn(string $name) => $name === 'di.layouts' ? [$vm::class => 'override.layout'] : null
        );

        self::assertSame('override.layout', $view->resolveLayout());
    }

    #[Test]
    public function guessNameRemovesConfiguredNamespaceRootWhenNamespaceStartsWithIt(): void
    {
        $vm = new ViewModelStub();
        $ns = new ReflectionClass($vm)->getNamespaceName();

        [$view, $app] = $this->createView($vm);
        $app->method('config')->willReturn($ns);

        self::assertSame('', $view->guessName($vm));
    }

    #[Test]
    public function guessNameFallsBackToModuleSegmentWhenNamespaceDoesNotStartWithRoot(): void
    {
        $vm = new DummyViewModel();

        [$view, $app] = $this->createView($vm);
        $app->method('config')->willReturn('App');

        self::assertSame('Front\\Home', $view->guessName($vm));
    }

    #[Test]
    public function addPathDelegatesToRendererService(): void
    {
        [$view, , $renderer] = $this->createView(new ViewModelStub(), mockRenderer: true);

        $renderer->expects($this->once())
            ->method('addPath')
            ->with('/some/path', 50, 'other');

        self::assertSame($view, $view->addPath('/some/path', 50, 'other'));
    }

    #[Test]
    public function getPathsAndDumpPathsDelegateToRendererService(): void
    {
        [$view, , $renderer] = $this->createView(new ViewModelStub());

        $paths = new PriorityQueue();
        $renderer->method('getPaths')->willReturn($paths);
        $renderer->method('dumpPaths')->willReturn(['/a', '/b']);

        self::assertSame($paths, $view->getPaths());
        self::assertSame(['/a', '/b'], $view->dumpPaths());
    }

    #[Test]
    public function addViewPathAddsDirectoryNextToViewModelFile(): void
    {
        $vm = new ViewModelStub();

        [$view, , $renderer] = $this->createView($vm, mockRenderer: true);

        $expected = dirname(new ReflectionClass($vm)->getFileName()) . '/views';

        $renderer->expects($this->once())
            ->method('addPath')
            ->with($expected, PriorityQueue::LOW, 'main');

        self::assertSame($view, $view->addViewPath($vm));
    }

    #[Test]
    public function addParentViewPathAddsDirectoryNextToParentClassFile(): void
    {
        $vm = new ViewModelStubChild();

        [$view, , $renderer] = $this->createView($vm, mockRenderer: true);

        $expected = dirname(new ReflectionClass(ViewModelStub::class)->getFileName()) . '/views';

        $renderer->expects($this->once())
            ->method('addPath')
            ->with($expected, PriorityQueue::LOW, 'main');

        self::assertSame($view, $view->addParentViewPath());
    }

    #[Test]
    public function renderReturnsRedirectResponseImmediatelyWhenPrepareReturnsUri(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = new Uri('https://example.test/target');

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));

        $response = $view->render();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(['https://example.test/target'], $response->getHeader('location'));
    }

    #[Test]
    public function renderThrowsWhenArrayResponseHasNoResolvableLayout(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $this->expectException(LogicException::class);

        $view->render();
    }

    #[Test]
    public function renderProducesHtmlResponseFromArrayViewModelResponse(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $view->setLayout('site.default');

        $response = $view->render();

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertStringContainsString('<p>Hello</p>', (string) $response->getBody());
    }

    #[Test]
    public function renderProducesHtmlResponseFromStringViewModelResponse(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = '<p>Raw</p>';

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $response = $view->render();

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertStringContainsString('<p>Raw</p>', (string) $response->getBody());
    }

    #[Test]
    public function renderSubscribesViewModelWhenMarkedAsEventSubscriber(): void
    {
        $vm = new SubscriberViewModelStub();
        $vm->prepareReturn = '<p>Raw</p>';

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        // Should not throw: View::render() detects the #[EventSubscriber] attribute
        // on the view model and registers it via CoreEventAwareTrait::subscribe().
        $response = $view->render();

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function bootIsIdempotentAndOnlyAddsEventDealerOnce(): void
    {
        $app = $this->createMock(AppContext::class);
        $app->expects(self::once())->method('getEventDispatcher')->willReturn(new EventEmitter());

        $view = new View(
            new ViewModelStub(),
            $app,
            $this->createStub(RendererService::class),
            $this->createStub(HtmlFrame::class),
            $this->createStub(AssetService::class)
        );

        $view->boot();
        $view->boot();
    }

    #[Test]
    public function renderLayoutAppliesGivenLayoutBeforeRendering(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $response = $view->renderLayout('site.custom', ['title' => 'Hello']);

        self::assertSame('site.custom', $view->getLayout());
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertStringContainsString('<p>Hello</p>', (string) $response->getBody());
    }

    #[Test]
    public function findPrepareMethodFallsBackToAttributeDecoratedMethodWhenNoPrepareMethodExists(): void
    {
        $vm = new AttributeBasedViewModelStub();

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $response = $view->render();

        self::assertStringContainsString('FromAttribute', (string) $response->getBody());
    }

    #[Test]
    public function findPrepareMethodThrowsWhenNoPrepareMethodOrAttributeFound(): void
    {
        [$view, $app] = $this->createView(new \stdClass());
        $app->method('config')->willReturn('');

        $this->expectException(LogicException::class);

        $view->render();
    }

    #[Test]
    public function handleVMResponseUnwrapsWrapperInterfaceBeforeHandling(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = $this->createStub(WrapperInterface::class);
        $vm->prepareReturn->method('__invoke')->willReturn('<p>Unwrapped</p>');

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $response = $view->render();

        self::assertStringContainsString('<p>Unwrapped</p>', (string) $response->getBody());
    }

    #[Test]
    public function handleVMResponsePassesThroughResponseInterfaceDirectly(): void
    {
        $directResponse = new HtmlResponse();
        $directResponse->getBody()->write('<p>Direct</p>');

        $vm = new ViewModelStub();
        $vm->prepareReturn = $directResponse;

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $response = $view->render();

        self::assertSame($directResponse, $response);
        self::assertStringContainsString('<p>Direct</p>', (string) $response->getBody());
    }

    #[Test]
    public function handleVMResponseConvertsRouteUriToRedirectResponse(): void
    {
        $redirect = new RedirectResponse(new Uri('https://example.com/'));

        $routeUri = $this->createStub(RouteUri::class);
        $routeUri->method('toResponse')->willReturn($redirect);

        $vm = new ViewModelStub();
        $vm->prepareReturn = $routeUri;

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $response = $view->render();

        self::assertSame($redirect, $response);
    }

    #[Test]
    public function handleVMResponseThrowsForUnsupportedReturnType(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = 42;

        [$view, $app] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');

        $this->expectException(\UnexpectedValueException::class);

        $view->render();
    }

    #[Test]
    public function renderInjectsDataIntoPropAttributedPropertiesAndCallsViewMetadataMethods(): void
    {
        $vm = new AnnotatedViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer] = $this->createView($vm, ['is_child' => false]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $view->setLayout('site.default');
        $view->render(['name' => 'Hello']);

        self::assertSame('Hello', $vm->name);
        self::assertTrue($view->getData()['metaRegistered'] ?? false);
    }

    #[Test]
    public function prepareHtmlFrameAddsCssJsAndModuleAssetsWhenViteIsInactive(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer, $htmlFrame, $asset] = $this->createView(
            $vm,
            [
                'is_child' => true,
                'css' => [0 => 'global.css', 'site.default' => ['layout.css']],
                'js' => [0 => 'global.js', 'site.default' => ['layout.js']],
                'modules' => [0 => 'global.module', 'site.default' => ['layout.module']],
            ]
        );
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $cssCalls = [];
        $asset->method('css')->willReturnCallback(
            function (string $path) use (&$cssCalls, $asset) {
                $cssCalls[] = $path;

                return $this->createStub(AssetLink::class);
            }
        );

        $jsCalls = [];
        $asset->method('js')->willReturnCallback(
            function (string $path) use (&$jsCalls) {
                $jsCalls[] = $path;

                return $this->createStub(AssetLink::class);
            }
        );

        $moduleCalls = [];
        $asset->method('module')->willReturnCallback(
            function (string $path) use (&$moduleCalls) {
                $moduleCalls[] = $path;

                return $this->createStub(AssetLink::class);
            }
        );

        $view->setLayout('site.default');
        $view->render();

        $vmName = 'windwalker/tests/core/view/stub';

        self::assertContains("@view/{$vmName}/global.css", $cssCalls);
        self::assertContains("@view/{$vmName}/layout.css", $cssCalls);
        self::assertContains("@view/{$vmName}/global.js", $jsCalls);
        self::assertContains("@view/{$vmName}/layout.js", $jsCalls);
        self::assertContains("@view/{$vmName}/global.module", $moduleCalls);
        self::assertContains("@view/{$vmName}/layout.module", $moduleCalls);
    }

    #[Test]
    public function prepareHtmlFrameImportsJsByAppWhenViteIsActive(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer, $htmlFrame, $asset] = $this->createView(
            $vm,
            [
                'is_child' => true,
                'js' => [0 => 'global.js', 'site.default' => 'layout.js'],
            ]
        );
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        $app->method('service')->willReturn($this->createLangServiceStub());
        $app->method('isDebug')->willReturn(false);

        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        new ReflectionProperty(AssetService::class, 'vite')->setValue(
            $asset,
            (function () {
                $vite = $this->createStub(ViteResolver::class);
                $vite->method('isActive')->willReturn(true);

                return $vite;
            })()
        );

        $importCalls = [];
        $asset->method('importByApp')->willReturnCallback(
            function (string $name, array $options = []) use (&$importCalls, $asset) {
                $importCalls[] = $name;

                return $asset;
            }
        );

        $view->setLayout('site.default');
        $view->render();

        // The numeric-keyed entry (0 => 'global.js') is always imported, and the
        // entry keyed by the current layout name ('site.default' => ['layout.js'])
        // is imported too, matching View::prepareHtmlFrame()'s vite-active branch.
        self::assertCount(2, $importCalls);
        self::assertStringStartsWith('view:', $importCalls[0]);
        self::assertStringStartsWith('view:', $importCalls[1]);
    }

    #[Test]
    public function preparePathsAddsRouteLayoutPathsAndLanguageAndSelfViewPaths(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        $route = $this->createStub(Route::class);
        $route->method('getOption')->willReturn(['/custom/layout/path']);

        [$view, $app, $renderer] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn($route);
        $app->method('service')->willReturn($this->createLangServiceStub());

        $addedPaths = [];
        $renderer->method('addPath')->willReturnCallback(
            function (string $path, int $priority = 100, string $ns = 'main') use (&$addedPaths, $renderer) {
                $addedPaths[] = $path;

                return $renderer;
            }
        );
        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $view->setLayout('site.default');
        $view->render();

        self::assertContains('/custom/layout/path', $addedPaths);

        $viewsDir = new ReflectionClass(ViewModelStub::class)->getFileName();
        $viewsDir = dirname($viewsDir) . '/views';

        self::assertContains($viewsDir . '/en', $addedPaths);
        self::assertContains($viewsDir, $addedPaths);
    }

    #[Test]
    public function preparePathsAddsFallbackLocaleDirWhenDifferentFromLocale(): void
    {
        $vm = new ViewModelStub();
        $vm->prepareReturn = ['title' => 'Hello'];

        [$view, $app, $renderer] = $this->createView($vm, ['is_child' => true]);
        $app->method('call')->willReturnCallback(fn(\Closure $c) => $c($app, $view));
        $app->method('config')->willReturn('');
        $app->method('path')->willReturn('/tmp/does-not-exist');
        $app->method('getMatchedRoute')->willReturn(null);
        // locale ('zh-TW') differs from fallback ('en'), so both language
        // directories should be added in View::preparePaths().
        $app->method('service')->willReturn($this->createLangServiceStub('zh-TW', 'en'));

        $addedPaths = [];
        $renderer->method('addPath')->willReturnCallback(
            function (string $path, int $priority = 100, string $ns = 'main') use (&$addedPaths, $renderer) {
                $addedPaths[] = $path;

                return $renderer;
            }
        );
        $renderer->method('getGlobals')->willReturn([]);
        $renderer->method('render')->willReturn('<p>Hello</p>');

        $view->setLayout('site.default');
        $view->render();

        $viewsDir = new ReflectionClass(ViewModelStub::class)->getFileName();
        $viewsDir = dirname($viewsDir) . '/views';

        self::assertContains($viewsDir . '/zh-TW', $addedPaths);
        self::assertContains($viewsDir . '/en', $addedPaths);
    }
}
