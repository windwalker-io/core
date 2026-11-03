<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Html;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Html\Metadata;

class MetadataTest extends TestCase
{
    #[Test]
    public function addMetadataAddsATagForEachArrayContentItem(): void
    {
        $metadata = new Metadata();

        $metadata->addMetadata('keywords', 'foo');
        $metadata->addMetadata('keywords', 'bar');

        $tags = $metadata->getMetadata()['keywords'];

        self::assertCount(2, $tags);
        self::assertStringContainsString('name="keywords"', (string) $tags[0]);
        self::assertStringContainsString('content="foo"', (string) $tags[0]);
        self::assertStringContainsString('content="bar"', (string) $tags[1]);
    }

    #[Test]
    public function addMetadataWithReplaceResetsExistingTagsForThatName(): void
    {
        $metadata = new Metadata();

        $metadata->addMetadata('keywords', 'foo');
        $metadata->addMetadata('keywords', 'bar', true);

        $tags = $metadata->getMetadata()['keywords'];

        self::assertCount(1, $tags);
        self::assertStringContainsString('content="bar"', (string) $tags[0]);
    }

    #[Test]
    public function addMetadataEscapesNameAndContent(): void
    {
        $metadata = new Metadata();

        $metadata->addMetadata('"><script>', '"><script>alert(1)</script>');

        $tag = (string) $metadata->getMetadata()['"><script>'][0];

        // Metadata::escape() pre-escapes the value, and the DOM renderer escapes
        // attribute values again on output, so entities end up double-encoded.
        self::assertStringNotContainsString('<script>', $tag);
        self::assertStringContainsString('&amp;quot;&amp;gt;', $tag);
    }

    #[Test]
    public function removeMetadataRemovesAllTagsForThatName(): void
    {
        $metadata = new Metadata();

        $metadata->addMetadata('keywords', 'foo');
        $metadata->removeMetadata('keywords');

        self::assertArrayNotHasKey('keywords', $metadata->getMetadata());
    }

    #[Test]
    public function removeMetadataIsSafeWhenNameDoesNotExist(): void
    {
        $metadata = new Metadata();

        $metadata->removeMetadata('not-exists');

        self::assertSame([], $metadata->getMetadata());
    }

    #[Test]
    public function addOpenGraphAddsATagForEachArrayContentItem(): void
    {
        $metadata = new Metadata();

        $metadata->addOpenGraph('og:image', 'a.png');
        $metadata->addOpenGraph('og:image', 'b.png');

        $tags = $metadata->getOpenGraphs()['og:image'];

        self::assertCount(2, $tags);
        self::assertStringContainsString('property="og:image"', (string) $tags[0]);
        self::assertStringContainsString('content="a.png"', (string) $tags[0]);
        self::assertStringContainsString('content="b.png"', (string) $tags[1]);
    }

    #[Test]
    public function addOpenGraphWithReplaceResetsExistingTagsForThatType(): void
    {
        $metadata = new Metadata();

        $metadata->addOpenGraph('og:image', 'a.png');
        $metadata->addOpenGraph('og:image', 'b.png', true);

        $tags = $metadata->getOpenGraphs()['og:image'];

        self::assertCount(1, $tags);
        self::assertStringContainsString('content="b.png"', (string) $tags[0]);
    }

    #[Test]
    public function removeOpenGraphRemovesAllTagsForThatType(): void
    {
        $metadata = new Metadata();

        $metadata->addOpenGraph('og:image', 'a.png');
        $metadata->removeOpenGraph('og:image');

        self::assertArrayNotHasKey('og:image', $metadata->getOpenGraphs());
    }

    #[Test]
    public function removeOpenGraphIsSafeWhenTypeDoesNotExist(): void
    {
        $metadata = new Metadata();

        $metadata->removeOpenGraph('not-exists');

        self::assertSame([], $metadata->getOpenGraphs());
    }

    #[Test]
    public function createOpenGraphTagBuildsAMetaElementWithPropertyAndContent(): void
    {
        $metadata = new Metadata();

        $tag = (string) $metadata->createOpenGraphTag('og:title', 'My Title');

        self::assertStringContainsString('property="og:title"', $tag);
        self::assertStringContainsString('content="My Title"', $tag);
    }

    #[Test]
    public function escapeConvertsSpecialCharactersToHtmlEntities(): void
    {
        $metadata = new Metadata();

        self::assertSame(
            '&quot;&gt;&lt;script&gt;',
            $metadata->escape('"><script>')
        );
    }
}
