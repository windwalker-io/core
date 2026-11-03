<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Language;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Runtime\Config;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Language\Language;
use Windwalker\Tests\Core\Language\Stub\DemoLanguagePackage;

#[CoversClass(LangService::class)]
class LangServiceTest extends TestCase
{
    /**
     * @var  string[]
     */
    protected array $tempDirs = [];

    #[After]
    protected function removeTempDirs(): void
    {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                Filesystem::delete($dir);
            }
        }

        $this->tempDirs = [];
    }

    protected function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/ww-lang-test-' . uniqid();

        Filesystem::mkdir($dir);

        $this->tempDirs[] = $dir;

        return $dir;
    }

    protected function writeLangFile(string $dir, string $locale, string $file, array $strings): void
    {
        $code = '<?php return ' . var_export($strings, true) . ';' . "\n";

        Filesystem::write($dir . '/' . $locale . '/' . $file, $code);
    }

    protected function createService(array $configData = []): LangService
    {
        $config = new Config($configData);
        $pathResolver = new PathResolver($config);

        return new LangService($config, $pathResolver);
    }

    #[Test]
    public function constructAddsConfiguredLanguagePaths(): void
    {
        $service = $this->createService(
            [
                'language' => [
                    'paths' => ['/foo/bar', '/baz'],
                ],
            ]
        );

        self::assertSame(
            [
                DIRECTORY_SEPARATOR . 'foo' . DIRECTORY_SEPARATOR . 'bar',
                DIRECTORY_SEPARATOR . 'baz',
            ],
            $service->dumpPaths()
        );
    }

    #[Test]
    public function constructWorksWithoutLanguagePaths(): void
    {
        $service = $this->createService();

        self::assertSame([], $service->dumpPaths());
    }

    #[Test]
    public function getLanguageUsesConfigDefaults(): void
    {
        $service = $this->createService();

        $language = $service->getLanguage();

        self::assertSame('en-US', $language->getLocale());
        self::assertSame('en-US', $language->getFallback());
        self::assertFalse($language->isDebug());
    }

    #[Test]
    public function getLanguageUsesConfigValues(): void
    {
        $service = $this->createService(
            [
                'language' => [
                    'locale' => 'zh-tw',
                    'fallback' => 'ja-jp',
                ],
            ]
        );

        $language = $service->getLanguage();

        self::assertSame('zh-TW', $language->getLocale());
        self::assertSame('ja-JP', $language->getFallback());
    }

    #[Test]
    public function getLanguageDebugRequiresBothAppAndLanguageDebugFlags(): void
    {
        self::assertTrue(
            $this->createService(['app' => ['debug' => true], 'language' => ['debug' => true]])
                ->getLanguage()
                ->isDebug()
        );

        self::assertFalse(
            $this->createService(['app' => ['debug' => true], 'language' => ['debug' => false]])
                ->getLanguage()
                ->isDebug()
        );

        self::assertFalse(
            $this->createService(['app' => ['debug' => false], 'language' => ['debug' => true]])
                ->getLanguage()
                ->isDebug()
        );
    }

    #[Test]
    public function getLanguageReturnsSameInstanceOnSubsequentCalls(): void
    {
        $service = $this->createService();

        self::assertSame($service->getLanguage(), $service->getLanguage());
    }

    #[Test]
    public function setLanguageOverridesLazyCreatedInstance(): void
    {
        $service = $this->createService();
        $language = new Language('fr-FR', 'fr-FR');

        $service->setLanguage($language);

        self::assertSame($language, $service->getLanguage());
    }

    #[Test]
    public function checkLanguageInstalledDoesNotThrowWhenPackageIsPresent(): void
    {
        LangService::checkLanguageInstalled();

        self::assertTrue(class_exists(Language::class));
    }

    #[Test]
    public function cloneCreatesIndependentPathsAndLanguage(): void
    {
        $service = $this->createService(['language' => ['paths' => ['/foo']]]);
        $service->getLanguage();

        $clone = clone $service;

        $clone->addPath('/bar');

        self::assertSame([DIRECTORY_SEPARATOR . 'foo'], $service->dumpPaths());
        self::assertSame(
            [DIRECTORY_SEPARATOR . 'foo', DIRECTORY_SEPARATOR . 'bar'],
            $clone->dumpPaths()
        );

        self::assertNotSame($service->getLanguage(), $clone->getLanguage());
    }

    #[Test]
    public function cloneDoesNotThrowWhenLanguageWasNeverInitialized(): void
    {
        // Regression test: LangService::$language defaults to null until
        // getLanguage() lazily creates it. __clone() must not attempt to
        // `clone` a null value.
        $service = $this->createService();

        $clone = clone $service;

        self::assertInstanceOf(Language::class, $clone->getLanguage());
        self::assertNotSame($service->getLanguage(), $clone->getLanguage());
    }

    #[Test]
    public function extractReturnsNewNamespacedServiceLinkedToParent(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['greeting' => 'Hello']);

        $service = $this->createService(['language' => ['paths' => [$dir]]]);
        $service->loadFile('messages');

        $extracted = $service->extract('plugin');
        $extracted->loadFile('messages');

        self::assertNotSame($service, $extracted);
        self::assertSame($service, $extracted->getParent());
        self::assertTrue($extracted->has('greeting'));
        self::assertFalse($service->has('plugin.greeting'));
    }

    #[Test]
    public function extractDoesNotThrowWhenLanguageWasNeverInitialized(): void
    {
        // Regression test: extract() clones $this before guaranteeing
        // $language is non-null, so the clone bug would surface here too.
        $service = $this->createService();

        $extracted = $service->extract('plugin');

        self::assertSame($service, $extracted->getParent());
    }

    #[Test]
    public function cloneForLocaleReturnsIndependentServiceWithNewLocale(): void
    {
        $service = $this->createService(['language' => ['locale' => 'en-US', 'fallback' => 'en-US']]);
        $service->getLanguage();

        $clone = $service->cloneForLocale('zh-TW');

        self::assertNotSame($service, $clone);
        self::assertSame('en-US', $service->getLocale());
        self::assertSame('zh-TW', $clone->getLocale());
    }

    #[Test]
    public function cloneForLocaleDoesNotThrowWhenLanguageWasNeverInitialized(): void
    {
        // Regression test, same root cause as the clone() tests above.
        $service = $this->createService();

        $clone = $service->cloneForLocale('zh-TW');

        self::assertSame('zh-TW', $clone->getLocale());
    }

    #[Test]
    public function callDelegatesToUnderlyingLanguage(): void
    {
        $service = $this->createService();

        self::assertSame('en-US', $service->getLocale());
    }

    #[Test]
    public function callReturnsSelfWhenLanguageReturnsItself(): void
    {
        $service = $this->createService();

        $result = $service->setLocale('fr-FR');

        self::assertSame($service, $result);
        self::assertSame('fr-FR', $service->getLocale());
    }

    #[Test]
    public function loadFileFromPathLoadsFallbackAndLocaleWhenNotDebug(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['only_fallback' => 'Fallback only', 'greeting' => 'Hello']);
        $this->writeLangFile($dir, 'zh-TW', 'messages.php', ['greeting' => '哈囉']);

        $service = $this->createService(['language' => ['locale' => 'zh-TW', 'fallback' => 'en-US']]);
        $service->loadFileFromPath($dir, 'messages');

        // Not debug: both the fallback file and the locale file are loaded.
        self::assertTrue($service->has('only_fallback'));
        self::assertTrue($service->has('greeting'));
        self::assertSame('哈囉', $service->trans('greeting'));
    }

    #[Test]
    public function loadFileFromPathSkipsFallbackFileWhenDebugAndLocaleDiffers(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['only_fallback' => 'Fallback only', 'greeting' => 'Hello']);
        $this->writeLangFile($dir, 'zh-TW', 'messages.php', ['greeting' => '哈囉']);

        $service = $this->createService(
            [
                'app' => ['debug' => true],
                'language' => ['debug' => true, 'locale' => 'zh-TW', 'fallback' => 'en-US'],
            ]
        );
        $service->loadFileFromPath($dir, 'messages');

        // Debug + locale !== fallback: only the locale-specific file is loaded,
        // so the fallback-only key was never loaded into the strings table.
        self::assertFalse($service->has('only_fallback'));
        self::assertTrue($service->has('greeting'));
    }

    #[Test]
    public function loadFileFromPathLoadsOnceWhenLocaleEqualsFallback(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['greeting' => 'Hello']);

        $service = $this->createService(['language' => ['locale' => 'en-US', 'fallback' => 'en-US']]);
        $service->loadFileFromPath($dir, 'messages');

        self::assertTrue($service->has('greeting'));
    }

    #[Test]
    public function loadFileFromPathStripsDuplicateExtension(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['greeting' => 'Hello']);

        $service = $this->createService(['language' => ['locale' => 'en-US', 'fallback' => 'en-US']]);
        $service->loadFileFromPath($dir, 'messages.php');

        self::assertTrue($service->has('greeting'));
    }

    #[Test]
    public function loadFileIteratesAllRegisteredPaths(): void
    {
        $dirA = $this->createTempDir();
        $dirB = $this->createTempDir();
        $this->writeLangFile($dirA, 'en-US', 'messages.php', ['from_a' => 'A']);
        $this->writeLangFile($dirB, 'en-US', 'messages.php', ['from_b' => 'B']);

        $service = $this->createService(['language' => ['paths' => [$dirA, $dirB]]]);
        $service->loadFile('messages');

        self::assertTrue($service->has('from_a'));
        self::assertTrue($service->has('from_b'));
    }

    #[Test]
    public function loadFileFromVendorResolvesVendorAlias(): void
    {
        $vendorDir = $this->createTempDir();
        $this->writeLangFile($vendorDir . '/acme/pkg/resources/languages', 'en-US', 'messages.php', ['greeting' => 'Hello']);

        $service = $this->createService(['@vendor' => $vendorDir]);
        $service->loadFileFromVendor('acme/pkg', 'messages');

        self::assertTrue($service->has('greeting'));
    }

    #[Test]
    public function loadAllFromPathLoadsAllMatchingFilesCaseInsensitively(): void
    {
        $dir = $this->createTempDir();
        $this->writeLangFile($dir, 'en-US', 'messages.php', ['greeting' => 'Hello']);
        $this->writeLangFile($dir, 'en-US', 'other.php', ['farewell' => 'Bye']);
        Filesystem::write($dir . '/en-US/skip.ini', "greeting=Nope\n");

        $service = $this->createService(['language' => ['locale' => 'en-US', 'fallback' => 'en-US']]);
        // Uppercase format argument exercises the strcasecmp() extension match.
        $service->loadAllFromPath($dir, 'PHP');

        self::assertTrue($service->has('greeting'));
        self::assertTrue($service->has('farewell'));
        self::assertSame('Hello', $service->trans('greeting'));
    }

    #[Test]
    public function loadAllLoadsFromAllRegisteredPaths(): void
    {
        $dirA = $this->createTempDir();
        $dirB = $this->createTempDir();
        $this->writeLangFile($dirA, 'en-US', 'a.php', ['from_a' => 'A']);
        $this->writeLangFile($dirB, 'en-US', 'b.php', ['from_b' => 'B']);

        $service = $this->createService(['language' => ['paths' => [$dirA, $dirB]]]);
        $service->loadAll();

        self::assertTrue($service->has('from_a'));
        self::assertTrue($service->has('from_b'));
    }

    #[Test]
    public function loadAllFromVendorResolvesPackageClassNameToVendorName(): void
    {
        $vendorDir = $this->createTempDir();
        $this->writeLangFile(
            $vendorDir . '/demo-package/resources/languages',
            'en-US',
            'messages.php',
            ['greeting' => 'Hello']
        );

        $service = $this->createService(['@vendor' => $vendorDir]);
        $service->loadAllFromVendor(DemoLanguagePackage::class, 'php');

        self::assertTrue($service->has('greeting'));
    }
}
