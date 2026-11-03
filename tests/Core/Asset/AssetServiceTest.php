<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Asset;

use OutOfRangeException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Asset\ViteResolver;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Runtime\Config;
use Windwalker\Event\EventEmitter;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\Wrapper\RawWrapper;

class AssetServiceTest extends TestCase
{
    private string $tmpDir;

    #[Before]
    protected function prepareTempDir(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/windwalker-asset-test-' . uniqid('', true);

        mkdir($this->tmpDir . '/public/assets', 0777, true);
    }

    #[After]
    protected function removeTempDir(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    /**
     * @param  array<string, mixed>  $configData
     */
    private function createService(
        array $configData = [],
        bool $debug = true,
        string $secret = 'test-secret',
        ?ApplicationInterface $app = null,
    ): AssetService {
        $config = new Config(
            array_replace_recursive(
                [
                    'asset' => [
                        'folder' => 'assets',
                        'version_file' => '@root/version.txt',
                        'vite' => [
                            'manifest' => '@public/assets/manifest.json',
                            'base' => 'resources/assets',
                        ],
                    ],
                    '@root' => $this->tmpDir,
                    '@public' => $this->tmpDir . '/public',
                ],
                $configData
            )
        );

        if ($app === null) {
            $app = $this->createStub(ApplicationInterface::class);
            $app->method('isDebug')->willReturn($debug);
            $app->method('getSecret')->willReturn($secret);
            $app->method('config')->willReturnCallback(
                static fn(string $name, ?string $delimiter = '.') => $config->getDeep($name, $delimiter ?? '.')
            );
        }

        $systemUri = new SystemUri('http://example.com/');
        $pathResolver = new PathResolver($config);
        $vite = new ViteResolver($config, $pathResolver);

        return new AssetService($app, $config, $systemUri, $pathResolver, $vite, new EventEmitter());
    }

    private function writeAssetFile(string $relativePath, string $content): string
    {
        $file = $this->tmpDir . '/public/assets/' . $relativePath;

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, $content);

        return $file;
    }

    #[Test]
    public function constructorDerivesPathAndRootFromSystemUriAndFolder(): void
    {
        $service = $this->createService();

        self::assertSame('/assets/', $service->path());
        self::assertSame('http://example.com/assets/', $service->root());
    }

    #[Test]
    public function constructorUsesConfiguredUriOverrideForPathAndRoot(): void
    {
        $service = $this->createService(['asset' => ['uri' => '/custom-base']]);

        self::assertSame('/custom-base/', $service->path());
        self::assertSame('/custom-base/', $service->root());
    }

    #[Test]
    public function getUndefinedPropertyThrows(): void
    {
        $service = $this->createService();

        $this->expectException(OutOfRangeException::class);

        $service->__get('nothing');
    }

    #[Test]
    public function cssAndJsRegisterLinksAndCacheSameKey(): void
    {
        $service = $this->createService();

        $link1 = $service->css('app.css', ['version' => false]);
        $link2 = $service->css('app.css', ['version' => false]);

        self::assertSame($link1, $link2, 'Same url/options should hit the once() cache.');
        self::assertArrayHasKey($link1->getHref(), $service->getStyles());

        $jsLink = $service->js('app.js');
        self::assertArrayHasKey($jsLink->getHref(), $service->getScripts());
    }

    #[Test]
    public function footerCssAndJsAsyncSetOptionsAndAttributes(): void
    {
        $service = $this->createService();

        $footer = $service->footerCSS('footer.css');
        self::assertTrue($footer->isFooter());

        $async = $service->jsAsync('async.js');
        self::assertTrue((bool) ($async->getAttributes()['async'] ?? false));

        $module = $service->module('mod.js');
        self::assertSame('module', $module->getAttributes()['type'] ?? null);
    }

    #[Test]
    public function aliasResolvesThroughChainOfAliases(): void
    {
        $service = $this->createService();

        $service->alias('a.js', 'b.js');
        $service->alias('b.js', 'c.js');

        self::assertSame('c.js', $service->resolveAlias('a.js'));
    }

    #[Test]
    public function resolveAliasFollowsImportMapPrefixes(): void
    {
        $service = $this->createService();

        $service->importMap('@vue/', 'https://cdn.example.com/vue/');

        self::assertSame(
            'https://cdn.example.com/vue/lib.js',
            $service->resolveAlias('@vue/lib.js')
        );
    }

    #[Test]
    public function handleUriPrefersUncompressedInDebugAndMinifiedInProduction(): void
    {
        $this->writeAssetFile('app.css', '/* src */');
        $this->writeAssetFile('app.min.css', '/* min */');

        $debugService = $this->createService(debug: true);
        self::assertSame('/assets/app.css', $debugService->handleUri('app.css'));

        $prodService = $this->createService(debug: false);
        self::assertSame('/assets/app.min.css', $prodService->handleUri('app.css'));
    }

    #[Test]
    public function handleUriFallsBackToOriginalUriWhenFileMissing(): void
    {
        $service = $this->createService();

        self::assertSame('/assets/missing.css', $service->handleUri('missing.css'));
    }

    #[Test]
    public function handleUriReturnsFullUrlUnchanged(): void
    {
        $service = $this->createService();

        self::assertSame(
            'https://example.org/a.js',
            $service->handleUri('https://example.org/a.js')
        );
    }

    #[Test]
    public function normalizeUriComputesPlainAndMinVariants(): void
    {
        $service = $this->createService();

        $result = $service->normalizeUri('foo.min.js', $assetFile, $assetMinFile);

        self::assertSame(['foo.js', 'foo.min.js'], $result);
        self::assertSame('foo.js', $assetFile);
        self::assertSame('foo.min.js', $assetMinFile);

        $service->normalizeUri('bar.js', $assetFile2, $assetMinFile2);

        self::assertSame('bar.js', $assetFile2);
        self::assertSame('bar.min.js', $assetMinFile2);
    }

    #[Test]
    public function hasReturnsAssetBasePathWhenFileExistsAndFalseWhenMissing(): void
    {
        $this->writeAssetFile('known.js', '// known');

        $service = $this->createService();

        self::assertSame('/assets/known.js', $service->has('known.js'));
        self::assertFalse($service->has('unknown.js'));
        self::assertSame('https://full.example/x.js', $service->has('https://full.example/x.js'));
    }

    #[Test]
    public function getVersionReturnsUidWhenAppIsDebug(): void
    {
        $service = $this->createService(debug: true);

        $version = $service->getVersion();

        self::assertNotSame('', $version);
        self::assertSame($version, $service->getVersion(), 'Version must be cached on the instance.');
    }

    #[Test]
    public function getVersionReadsTrimmedContentFromVersionFileWhenPresent(): void
    {
        file_put_contents($this->tmpDir . '/version.txt', "  abc123  \n");

        $service = $this->createService(debug: false);

        self::assertSame('abc123', $service->getVersion());
    }

    #[Test]
    public function getVersionFallsBackToDetectedHashWhenFileMissing(): void
    {
        $service = $this->createService(debug: false);

        $version = $service->getVersion();

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $version);
    }

    #[Test]
    public function appendVersionAddsQueryParameterWithCorrectSeparator(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        self::assertSame('a.js?v1', $service->appendVersion('a.js'));
        self::assertSame('a.js?x=1&v1', $service->appendVersion('a.js?x=1'));
        self::assertSame('a.js?v2', $service->appendVersion('a.js', 'v2'));
    }

    #[Test]
    public function internalCssAndJsAreGroupedByFooterAndModuleOptions(): void
    {
        $service = $this->createService();

        $service->internalCSS('.a{}');
        $service->footerInternalCSS('.b{}');

        self::assertSame('.a{}', $service->renderInternalCSS(false));
        self::assertSame('.b{}', $service->renderInternalCSS(true));

        $service->internalJS('var a = 1;');
        $service->internalModule('var b = 2;');

        self::assertSame('var a = 1;', $service->renderInternalJS(false));
        self::assertSame('var b = 2;', $service->renderInternalJS(true));
    }

    #[Test]
    public function renderCssOutputsLinkTagsWithVersionAndSri(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->css('style.css', ['sri' => 'sha384-abc']);
        $service->internalCSS('.inline{}');

        $html = $service->renderCSS(true);

        self::assertStringContainsString('rel="stylesheet"', $html);
        self::assertStringContainsString('v1', $html);
        self::assertStringContainsString('integrity="sha384-abc"', $html);
        self::assertStringContainsString('.inline{}', $html);
    }

    #[Test]
    public function renderCssSkipsLinksNotMatchingFooterFilter(): void
    {
        $service = $this->createService(debug: true);

        $service->css('head.css');
        $service->css('footer.css', ['footer' => true]);

        $headHtml = $service->renderCSS(false, [], false);
        $footerHtml = $service->renderCSS(false, [], true);

        self::assertStringContainsString('head.css', $headHtml);
        self::assertStringNotContainsString('footer.css', $headHtml);

        self::assertStringContainsString('footer.css', $footerHtml);
        self::assertStringNotContainsString('head.css', $footerHtml);
    }

    #[Test]
    public function renderJsOutputsScriptTagsAndInternalScripts(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->js('app.js');
        $service->internalJS('var inline = true;');

        $html = $service->renderJS(true);

        self::assertStringContainsString('src="/assets/app.js?v1"', $html);
        self::assertStringContainsString('var inline = true;', $html);
    }

    #[Test]
    public function importModuleCreatesScopeOnceAndAppendsBody(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->importModule('@main', "console.log('a')");
        $service->importModule('@main', "console.log('b')");

        $html = $service->renderJS(true);

        self::assertStringContainsString("import('@main')", $html);
        self::assertStringContainsString("console.log('a')", $html);
        self::assertStringContainsString("console.log('b')", $html);
    }

    #[Test]
    public function importMainThenUsesDefaultMainModuleName(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->importMainThen('doSomething()');

        $html = $service->renderJS(true);

        self::assertStringContainsString("import('@main')", $html);
        self::assertStringContainsString('doSomething()', $html);
    }

    #[Test]
    public function importByAppUsesDefaultCodeWhenNoHandlerConfigured(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->importByApp('my-module');

        $html = $service->renderJS(true);

        self::assertStringContainsString("module.default.import('my-module')", $html);
    }

    #[Test]
    public function importByAppUsesConfiguredClosureHandler(): void
    {
        $service = $this->createService(
            configData: [
                'asset' => [
                    'modules' => [
                        'importCode' => static fn(string $module, array $options) => "custom_import($module);",
                    ],
                ],
            ],
            debug: true,
        );
        $service->setVersion('v1');

        $service->importByApp('my-module');

        $html = $service->renderJS(true);

        self::assertStringContainsString('custom_import(my-module);', $html);
    }

    #[Test]
    public function importSyncByAppUsesDefaultCodeAndEncodesProps(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->importSyncByApp('my-module', ['foo' => 'bar']);

        $html = $service->renderJS(true);

        self::assertStringContainsString("module.default.importSync('my-module', {foo:\"bar\"})", $html);
    }

    #[Test]
    public function teleportStartAndEndCapturesOutputBuffer(): void
    {
        $service = $this->createService();

        $service->startTeleport('modal');
        echo 'Hello Modal';
        $service->endTeleport();

        self::assertStringContainsString('Hello Modal', $service->getTeleport()->render());
    }

    #[Test]
    public function getImportMapIsLazilyCreatedAndSharedAcrossCalls(): void
    {
        $service = $this->createService();

        self::assertSame($service->getImportMap(), $service->getImportMap());
    }

    #[Test]
    public function setImportMapsAddsImportsAndScopes(): void
    {
        $service = $this->createService();

        $service->setImportMaps(
            [
                'imports' => [
                    'vue' => new RawWrapper('https://cdn.example.com/vue.js'),
                ],
                'scopes' => [
                    '/scope/' => [
                        'lodash' => 'https://cdn.example.com/lodash.js',
                    ],
                ],
            ]
        );

        $map = $service->getImportMap();

        self::assertSame('https://cdn.example.com/vue.js', $map->getImport('vue'));
        self::assertSame(
            'https://cdn.example.com/lodash.js',
            $map->getAllScopes()['/scope/']['lodash'] ?? null
        );
    }

    #[Test]
    public function resetClearsStylesScriptsAndInternalAssetsOnly(): void
    {
        $service = $this->createService();

        $service->css('a.css');
        $service->js('a.js');
        $service->internalCSS('.a{}');
        $service->internalJS('var a = 1;');
        $service->alias('x.js', 'y.js');

        $service->reset();

        self::assertSame([], $service->getStyles());
        self::assertSame([], $service->getScripts());
        self::assertSame([], $service->getInternalStyles());
        self::assertSame([], $service->getInternalScripts());
        self::assertSame('y.js', $service->resolveAlias('x.js'), 'Aliases are not cleared by reset().');
    }

    #[Test]
    public function isDebugCachesApplicationIsDebugResult(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('isDebug')->willReturn(true);
        $app->method('getSecret')->willReturn('secret');
        $app->method('config')->willReturn(null);

        $service = $this->createService(app: $app);

        self::assertTrue($service->isDebug());
        self::assertTrue($service->isDebug());
    }

    #[Test]
    public function folderPathPrependsConfiguredFolder(): void
    {
        $service = $this->createService();

        self::assertSame('assets/', $service->folderPath());
        self::assertSame('assets/app.js/', $service->folderPath('app.js'));
    }

    #[Test]
    public function getJsObjectEncodesVariousPhpTypes(): void
    {
        self::assertSame('null', AssetService::getJSObject(null));
        self::assertSame('true', AssetService::getJSObject(true));
        self::assertSame('1', AssetService::getJSObject(1));
        self::assertSame('[1,2]', AssetService::getJSObject([1, 2]));
        self::assertSame('{a:1}', AssetService::getJSObject(['a' => 1]));
        self::assertSame('"a"', AssetService::getJSObject('a'));
        self::assertSame('raw', AssetService::getJSObject(new RawWrapper('raw')));
    }

    #[Test]
    public function isAbsoluteUrlAndIsFullUrlDetectUriKinds(): void
    {
        self::assertTrue(AssetService::isAbsoluteUrl('/foo'));
        self::assertTrue(AssetService::isAbsoluteUrl('http://foo'));
        self::assertFalse(AssetService::isAbsoluteUrl('foo'));

        self::assertTrue(AssetService::isFullUrl('http://foo'));
        self::assertFalse(AssetService::isFullUrl('/foo'));
    }

    #[Test]
    public function moduleAsyncSetsModuleTypeAndAsyncAttribute(): void
    {
        $service = $this->createService();

        $link = $service->moduleAsync('m.js');

        self::assertSame('module', $link->getAttributes()['type'] ?? null);
        self::assertTrue((bool) ($link->getAttributes()['async'] ?? false));
    }

    #[Test]
    public function importModuleAppendsCommentAfterBody(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->importModule('@main', 'doSomething()', ['comment' => '//note']);

        $html = $service->renderJS(true);

        self::assertStringContainsString('doSomething();//note', $html);
    }

    #[Test]
    public function importSyncByAppUsesConfiguredClosureHandler(): void
    {
        $service = $this->createService(
            configData: [
                'asset' => [
                    'modules' => [
                        'importSyncCode' => static fn(string $module, string $props, array $options)
                            => "customSync($module, $props);",
                    ],
                ],
            ],
            debug: true,
        );
        $service->setVersion('v1');

        $service->importSyncByApp('my-module', ['a' => 1]);

        $html = $service->renderJS(true);

        self::assertStringContainsString('customSync(my-module, {a:1});', $html);
    }

    #[Test]
    public function viteDevServerForcesNoVersionAndInjectsClientScript(): void
    {
        mkdir($this->tmpDir . '/tmp', 0777, true);
        file_put_contents($this->tmpDir . '/tmp/vite-server', 'http://localhost:5173');

        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $link = $service->js('@vite/app.js');

        self::assertFalse($link->getOption('version'));

        $html = $service->renderJS();

        self::assertStringContainsString('@vite/client', $html);
        self::assertStringNotContainsString('v1', $html);
    }

    #[Test]
    public function renderJsOutputsSriAndInlineBody(): void
    {
        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $service->js('app.js', ['sri' => 'sha384-xyz', 'body' => 'console.log(1)']);

        $html = $service->renderJS();

        self::assertStringContainsString('integrity="sha384-xyz"', $html);
        self::assertStringContainsString('crossorigin="anonymous"', $html);
        self::assertStringContainsString('console.log(1)', $html);
        self::assertStringNotContainsString('src=', $html);
    }

    #[Test]
    public function hasFallsBackToPlainOrMinifiedFileWhenRequestedVariantMissing(): void
    {
        $this->writeAssetFile('only-plain.js', '// plain');
        $this->writeAssetFile('only-min.min.js', '// min');

        $service = $this->createService();

        self::assertSame('/assets/only-plain.js', $service->has('only-plain.min.js'));
        self::assertSame('/assets/only-min.min.js', $service->has('only-min.js'));
    }

    #[Test]
    public function resolveAliasResolvesPlainImportMapNameWithoutSlash(): void
    {
        $service = $this->createService();

        $service->importMap('@app', 'https://cdn.example.com/app.js');

        self::assertSame('https://cdn.example.com/app.js', $service->resolveAlias('@app'));
    }

    #[Test]
    public function setImportMapsResolvesPlainStringImportsThroughPath(): void
    {
        $service = $this->createService();

        $service->setImportMaps(
            [
                'imports' => [
                    'local' => 'local-lib.js',
                ],
            ]
        );

        self::assertSame('/assets/local-lib.js', $service->getImportMap()->getImport('local'));
    }

    #[Test]
    public function appendVersionReturnsUriUnchangedWhenVersionIsFalsy(): void
    {
        file_put_contents($this->tmpDir . '/version.txt', '   ');

        $service = $this->createService(debug: false);

        self::assertSame('a.js', $service->appendVersion('a.js'));
    }

    #[Test]
    public function detectVersionCachesResultAcrossInstancesViaStaticVariable(): void
    {
        // detectVersion() uses a function-level static variable, so once computed in this
        // process it returns the cached hash for any later AssetService instance too.
        $service1 = $this->createService(debug: false);
        $version1 = $service1->getVersion();

        $service2 = $this->createService(debug: false);
        $version2 = $service2->getVersion();

        self::assertSame($version1, $version2);
    }

    #[Test]
    public function pathRootAndFolderPathAppendVersionQueryString(): void
    {
        $service = $this->createService();
        $service->setVersion('v1');

        self::assertSame('/assets/a.js?v1', $service->path('a.js', true));
        self::assertSame('/assets/a.js?x=1&v1', $service->path('a.js?x=1', true));
        self::assertSame('http://example.com/assets/a.js?v1', $service->root('a.js', true));
    }

    #[Test]
    public function addSysPathReturnsFullUrlUnchanged(): void
    {
        $service = $this->createService();

        self::assertSame('https://cdn.example.com/a.js', $service->addSysPath('https://cdn.example.com/a.js'));
    }

    #[Test]
    public function resolveViteUriDelegatesToViteResolver(): void
    {
        $service = $this->createService();

        self::assertSame('@vite/app.js', $service->resolveViteUri('@vite/app.js'));
    }

    #[Test]
    public function getPropertyPathAndRootDelegateToMethods(): void
    {
        $service = $this->createService();

        self::assertSame($service->path(), $service->__get('path'));
        self::assertSame($service->root(), $service->__get('root'));
    }

    #[Test]
    public function viteEntryAddsJsAndCssFromManifestEntries(): void
    {
        $manifest = [
            'resources/assets/app.js' => [
                'file' => 'assets/app.hash.js',
                'css' => ['assets/app.hash.css'],
            ],
        ];

        file_put_contents(
            $this->tmpDir . '/public/assets/manifest.json',
            json_encode($manifest, JSON_THROW_ON_ERROR)
        );

        $service = $this->createService(debug: true);
        $service->setVersion('v1');

        $links = $service->viteEntry('@vite/app.js');

        self::assertCount(2, $links);
        self::assertSame('/assets/app.hash.js', $links[0]->getHref());
        self::assertSame('/assets/app.hash.css', $links[1]->getHref());
    }

    #[Test]
    public function handleUriFallsBackWhenPreferredVariantMissing(): void
    {
        $this->writeAssetFile('only-min.min.css', '/* min */');
        $debugService = $this->createService(debug: true);

        self::assertSame('/assets/only-min.min.css', $debugService->handleUri('only-min.css'));

        $this->writeAssetFile('only-plain.css', '/* plain */');
        $prodService = $this->createService(debug: false);

        self::assertSame('/assets/only-plain.css', $prodService->handleUri('only-plain.css'));
    }

    #[Test]
    public function rootAppendsVersionAfterExistingQueryString(): void
    {
        $service = $this->createService();
        $service->setVersion('v1');

        self::assertSame('http://example.com/assets/a.js?x=1&v1', $service->root('a.js?x=1', true));
    }

    #[Test]
    public function folderPathWithUriAppendsVersionQueryString(): void
    {
        $service = $this->createService();
        $service->setVersion('v1');

        self::assertSame('assets/a.js?v1/', $service->folderPath('a.js', true));
        self::assertSame('assets/a.js?x=1&v1/', $service->folderPath('a.js?x=1', true));
    }

    #[Test]
    public function addUriBaseResolvesRelativeUriAndSkipsAbsoluteUrl(): void
    {
        $service = $this->createService();

        self::assertSame('https://cdn.example.com/a.js', $service->addUriBase('https://cdn.example.com/a.js'));
        self::assertSame('a.js/', $service->addUriBase('a.js'));
        self::assertSame('http://example.com/a.js', $service->addUriBase('a.js', 'root'));
        self::assertSame('example.com/a.js', $service->addUriBase('/a.js', 'root'));
    }

    #[Test]
    public function addAssetBaseStripsFolderPathPrefixAndAppliesHostForRoot(): void
    {
        $service = $this->createService();

        self::assertSame(
            'https://cdn.example.com/a.js',
            $service->addAssetBase('https://cdn.example.com/a.js')
        );
        self::assertSame('/assets/a.js', $service->addAssetBase('assets/a.js'));
        self::assertSame(
            'http://example.com/assets/a.js',
            $service->addAssetBase('assets/a.js', 'root')
        );
        self::assertSame(
            'http://example.com/a.js',
            $service->addAssetBase('/a.js', 'root')
        );
    }
}
