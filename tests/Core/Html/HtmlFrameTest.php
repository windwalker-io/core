<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Html;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\Html\Metadata;
use Windwalker\Core\Html\PreloadLink;
use Windwalker\Core\Link\Link;

class HtmlFrameTest extends TestCase
{
    protected function createFrame(?AssetService $asset = null): HtmlFrame
    {
        return new HtmlFrame($asset ?? $this->createStub(AssetService::class));
    }

    #[Test]
    public function getPageTitleReturnsTitleOnly(): void
    {
        $frame = $this->createFrame();
        $frame->setTitle('My Page');

        self::assertSame('My Page', $frame->getPageTitle());
    }

    #[Test]
    public function getPageTitleReturnsSiteNameOnly(): void
    {
        $frame = $this->createFrame();
        $frame->setSiteName('My Site');

        self::assertSame('My Site', $frame->getPageTitle());
    }

    #[Test]
    public function getPageTitleCombinesTitleAndSiteNameWithSeparator(): void
    {
        $frame = $this->createFrame();
        $frame->setTitle('My Page');
        $frame->setSiteName('My Site');

        self::assertSame('My Page | My Site', $frame->getPageTitle());
        self::assertSame('My Page - My Site', $frame->getPageTitle(' - '));
    }

    #[Test]
    public function getPageTitleReturnsNullWhenBothAreEmpty(): void
    {
        $frame = $this->createFrame();

        self::assertNull($frame->getPageTitle());
    }

    #[Test]
    public function setFaviconResolvesUriThroughAssetService(): void
    {
        $asset = $this->createMock(AssetService::class);
        $asset->expects(self::once())
            ->method('handleUri')
            ->with('favicon.ico')
            ->willReturn('/assets/favicon.ico');

        $frame = $this->createFrame($asset);
        $frame->setFavicon('favicon.ico');

        self::assertSame('/assets/favicon.ico', $frame->getFavicon());
    }

    #[Test]
    public function setFaviconAcceptsNull(): void
    {
        $asset = $this->createMock(AssetService::class);
        $asset->expects(self::never())->method('handleUri');

        $frame = $this->createFrame($asset);
        $frame->setFavicon(null);

        self::assertNull($frame->getFavicon());
    }

    #[Test]
    public function renderFaviconReturnsNullWhenNotSet(): void
    {
        $frame = $this->createFrame();

        self::assertNull($frame->renderFavicon());
    }

    #[Test]
    public function renderFaviconBuildsLinkTag(): void
    {
        $asset = $this->createStub(AssetService::class);
        $asset->method('handleUri')->willReturn('/assets/favicon.ico');

        $frame = $this->createFrame($asset);
        $frame->setFavicon('favicon.ico');

        $html = $frame->renderFavicon();

        self::assertStringContainsString('rel="shortcut icon"', $html);
        self::assertStringContainsString('type="image/x-icon"', $html);
        self::assertStringContainsString('href="/assets/favicon.ico"', $html);
    }

    #[Test]
    public function renderTitleWrapsPageTitleInTitleTag(): void
    {
        $frame = $this->createFrame();
        $frame->setTitle('My Page');
        $frame->setSiteName('My Site');

        self::assertSame('<title>My Page|My Site</title>', $frame->renderTitle());
    }

    #[Test]
    public function renderTitleRendersEmptyTagWhenNoTitleOrSiteName(): void
    {
        $frame = $this->createFrame();

        self::assertSame('<title></title>', $frame->renderTitle());
    }

    #[Test]
    public function addLinkWithStringRelCreatesLinkElement(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addLink('stylesheet', 'style.css', ['media' => 'screen']);

        self::assertStringContainsString('rel="stylesheet"', (string) $tag);
        self::assertStringContainsString('href="style.css"', (string) $tag);
        self::assertStringContainsString('media="screen"', (string) $tag);
        self::assertSame([$tag], $frame->getCustomTags());
    }

    #[Test]
    public function addLinkWithLinkInstanceUsesProvidedHref(): void
    {
        $frame = $this->createFrame();

        $link = new Link('preconnect', 'https://fonts.example.com');

        $tag = $frame->addLink($link);

        self::assertStringContainsString('rel="preconnect"', (string) $tag);
        self::assertStringContainsString('href="https://fonts.example.com"', (string) $tag);
    }

    #[Test]
    public function addLinkOverridesHrefWhenProvided(): void
    {
        $frame = $this->createFrame();

        $link = new Link('canonical', 'https://old.example.com');

        $tag = $frame->addLink($link, 'https://new.example.com');

        self::assertStringContainsString('href="https://new.example.com"', (string) $tag);
    }

    #[Test]
    public function addHreflangAddsAlternateLinkWithHreflangAttribute(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addHreflang('en-US', 'https://example.com/en');

        self::assertStringContainsString('rel="alternate"', (string) $tag);
        self::assertStringContainsString('hreflang="en-US"', (string) $tag);
        self::assertStringContainsString('href="https://example.com/en"', (string) $tag);
    }

    #[Test]
    public function addCanonicalAddsCanonicalLink(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addCanonical('https://example.com/page');

        self::assertStringContainsString('rel="canonical"', (string) $tag);
        self::assertStringContainsString('href="https://example.com/page"', (string) $tag);
    }

    #[Test]
    public function setRobotsMetaJoinsArrayContentWithComma(): void
    {
        $frame = $this->createFrame();

        $frame->setRobotsMeta(['noindex', 'nofollow']);

        $tags = $frame->getMetadata()->getMetadata()['robots'];

        self::assertCount(1, $tags);
        self::assertStringContainsString('content="noindex,nofollow"', (string) $tags[0]);
    }

    #[Test]
    public function setNoindexSetsRobotsMetaToNoindex(): void
    {
        $frame = $this->createFrame();

        $frame->setNoindex();

        $tags = $frame->getMetadata()->getMetadata()['robots'];

        self::assertStringContainsString('content="noindex"', (string) $tags[0]);
    }

    #[Test]
    public function addPreloadBuildsPreloadLinkWithAsTypeAndMedia(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addPreload(
            'font',
            '/fonts/foo.woff2',
            ['crossorigin' => 'anonymous'],
            'font/woff2',
            'screen'
        );

        self::assertStringContainsString('rel="preload"', (string) $tag);
        self::assertStringContainsString('as="font"', (string) $tag);
        self::assertStringContainsString('type="font/woff2"', (string) $tag);
        self::assertStringContainsString('media="screen"', (string) $tag);
        self::assertStringContainsString('crossorigin="anonymous"', (string) $tag);
    }

    #[Test]
    public function addPreloadOmitsTypeAndMediaWhenNotProvided(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addPreload('image', '/img/foo.png');

        self::assertStringNotContainsString('type=', (string) $tag);
        self::assertStringNotContainsString('media=', (string) $tag);
    }

    #[Test]
    public function linkToElementBuildsAnchorAttributesFromLink(): void
    {
        $link = (new PreloadLink('preload', '/foo.js'))->withAs('script');

        $html = (string) HtmlFrame::linkToElement($link);

        self::assertStringContainsString('<link', $html);
        self::assertStringContainsString('href="/foo.js"', $html);
        self::assertStringContainsString('rel="preload"', $html);
        self::assertStringContainsString('as="script"', $html);
    }

    #[Test]
    public function addCustomTagWithElementNameCreatesElement(): void
    {
        $frame = $this->createFrame();

        $tag = $frame->addCustomTag('meta', null, ['charset' => 'utf-8']);

        self::assertStringContainsString('charset="utf-8"', (string) $tag);
        self::assertSame([$tag], $frame->getCustomTags());
    }

    #[Test]
    public function addCustomTagAcceptsHtmlElementDirectly(): void
    {
        $frame = $this->createFrame();

        $element = \Windwalker\DOM\h('meta', ['name' => 'foo']);

        $tag = $frame->addCustomTag($element);

        self::assertSame($element, $tag);
        self::assertSame([$element], $frame->getCustomTags());
    }

    #[Test]
    public function renderMetadataJoinsMetadataAndOpenGraphTags(): void
    {
        $frame = $this->createFrame();
        $frame->setIndents('');
        $frame->addMetadata('description', 'foo');
        $frame->addOpenGraph('og:title', 'bar');

        $html = $frame->renderMetadata();

        self::assertStringContainsString('name="description"', $html);
        self::assertStringContainsString('content="foo"', $html);
        self::assertStringContainsString('property="og:title"', $html);
        self::assertStringContainsString('content="bar"', $html);
    }

    #[Test]
    public function renderMetadataReturnsEmptyStringWhenNoMetadata(): void
    {
        $frame = $this->createFrame();

        self::assertSame('', $frame->renderMetadata());
    }

    #[Test]
    public function renderCustomTagsJoinsAllAddedTags(): void
    {
        $frame = $this->createFrame();
        $frame->setIndents('');
        $frame->addCustomTag('meta', null, ['charset' => 'utf-8']);
        $frame->addCustomTag('meta', null, ['name' => 'viewport']);

        $html = $frame->renderCustomTags();

        self::assertStringContainsString('charset="utf-8"', $html);
        self::assertStringContainsString('name="viewport"', $html);
    }

    #[Test]
    public function renderCustomTagsReturnsEmptyStringWhenNoTags(): void
    {
        $frame = $this->createFrame();

        self::assertSame('', $frame->renderCustomTags());
    }

    #[Test]
    public function setDescriptionStripsTagsAndCollapsesWhitespace(): void
    {
        $frame = $this->createFrame();

        $frame->setDescription("<b>Hello   \n   World</b>");

        $metaTags = $frame->getMetadata()->getMetadata()['description'];
        $ogTags = $frame->getMetadata()->getOpenGraphs()['og:description'];

        self::assertStringContainsString('content="Hello World"', (string) $metaTags[0]);
        self::assertStringContainsString('content="Hello World"', (string) $ogTags[0]);
    }

    #[Test]
    public function setDescriptionTruncatesWhenLengthProvided(): void
    {
        $frame = $this->createFrame();

        $frame->setDescription('Hello World, this is long text.', 5);

        $tags = $frame->getMetadata()->getMetadata()['description'];

        self::assertStringContainsString('content="Hello..."', (string) $tags[0]);
    }

    #[Test]
    public function setDescriptionIfNotEmptySkipsWhenEmpty(): void
    {
        $frame = $this->createFrame();

        $frame->setDescriptionIfNotEmpty('');
        $frame->setDescriptionIfNotEmpty(null);

        self::assertSame([], $frame->getMetadata()->getMetadata());
    }

    #[Test]
    public function setDescriptionIfNotEmptySetsWhenNotEmpty(): void
    {
        $frame = $this->createFrame();

        $frame->setDescriptionIfNotEmpty('Hello');

        self::assertArrayHasKey('description', $frame->getMetadata()->getMetadata());
    }

    #[Test]
    public function setCoverImagesResolvesEachImageThroughAssetService(): void
    {
        $asset = $this->createMock(AssetService::class);
        $asset->expects(self::exactly(2))
            ->method('addAssetBase')
            ->willReturnMap([
                ['a.png', 'root', '/assets/a.png'],
                ['b.png', 'root', '/assets/b.png'],
            ]);

        $frame = $this->createFrame($asset);
        $frame->setCoverImages('a.png', 'b.png');

        $tags = $frame->getMetadata()->getOpenGraphs()['og:image'];

        self::assertCount(2, $tags);
        self::assertStringContainsString('content="/assets/a.png"', (string) $tags[0]);
        self::assertStringContainsString('content="/assets/b.png"', (string) $tags[1]);
    }

    #[Test]
    public function setCoverImagesClearsPreviousOgImageEntries(): void
    {
        $asset = $this->createStub(AssetService::class);
        $asset->method('addAssetBase')->willReturnArgument(0);

        $frame = $this->createFrame($asset);
        $frame->setCoverImages('a.png');
        $frame->setCoverImages('b.png');

        $tags = $frame->getMetadata()->getOpenGraphs()['og:image'];

        self::assertCount(1, $tags);
        self::assertStringContainsString('content="b.png"', (string) $tags[0]);
    }

    #[Test]
    public function setCoverImagesIfNotEmptySkipsWhenNoImagesProvided(): void
    {
        $asset = $this->createMock(AssetService::class);
        $asset->expects(self::never())->method('addAssetBase');

        $frame = $this->createFrame($asset);
        $frame->setCoverImagesIfNotEmpty();
        $frame->setCoverImagesIfNotEmpty('', '');

        self::assertSame([], $frame->getMetadata()->getOpenGraphs());
    }

    #[Test]
    public function setCoverImagesIfNotEmptyFiltersOutEmptyValuesButKeepsOthers(): void
    {
        $asset = $this->createStub(AssetService::class);
        $asset->method('addAssetBase')->willReturnArgument(0);

        $frame = $this->createFrame($asset);
        $frame->setCoverImagesIfNotEmpty('', 'a.png');

        $tags = $frame->getMetadata()->getOpenGraphs()['og:image'];

        self::assertCount(1, $tags);
        self::assertStringContainsString('content="a.png"', (string) $tags[0]);
    }
}
