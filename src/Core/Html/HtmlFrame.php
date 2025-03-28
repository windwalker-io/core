<?php

declare(strict_types=1);

namespace Windwalker\Core\Html;

use JetBrains\PhpStorm\ExpectedValues;
use Psr\Link\LinkInterface;
use Stringable;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Link\Link;
use Windwalker\DOM\DOMElement;
use Windwalker\DOM\HTMLElement;

use function Windwalker\DOM\h;
use function Windwalker\str;

/**
 * The HtmlFrame class.
 */
class HtmlFrame
{
    /**
     * Property title.
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * Property siteName.
     *
     * @var string|null
     */
    protected ?string $siteName = null;

    /**
     * Property favicon.
     *
     * @var string|null
     */
    protected ?string $favicon = null;

    /**
     * Property metadata.
     *
     * @var  Metadata
     */
    protected Metadata $metadata;

    /**
     * Property customTags.
     *
     * @var  array
     */
    protected array $customTags = [];

    /**
     * Property indents.
     *
     * @var  string
     */
    protected string $indents = '    ';

    /**
     * @var DOMElement
     */
    protected DOMElement $body;

    protected DOMElement $html;

    /**
     * HtmlHeaderManager constructor.
     *
     * @param  Metadata|null  $metadata
     */
    public function __construct(
        protected AssetService $asset,
        ?Metadata $metadata = null
    ) {
        $this->body = HTMLElement::create('body');
        $this->html = HTMLElement::create('html');
        $this->metadata = $metadata ?? new Metadata();
    }

    /**
     * Method to get property Title
     *
     * @param  string  $separator
     *
     * @return ?string
     */
    public function getPageTitle(string $separator = ' | '): ?string
    {
        $title = $this->title;

        if ($title && $this->siteName) {
            $title .= $separator . $this->siteName;
        } elseif (!$title) {
            $title = $this->siteName;
        }

        return $title;
    }

    /**
     * getTitle
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Method to set property title
     *
     * @param  string|null  $title
     *
     * @return  static  Return self to support chaining.
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Method to get property Favicon
     *
     * @return  string
     */
    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    /**
     * Method to set property favicon
     *
     * @param  string|null  $favicon
     *
     * @return  static  Return self to support chaining.
     */
    public function setFavicon(?string $favicon): static
    {
        $this->favicon = $favicon;

        return $this;
    }

    public function addLink(
        string|Stringable|LinkInterface $relOrLink,
        string|Stringable $href = '',
        array $attrs = []
    ): DOMElement {
        if (is_string($relOrLink)) {
            $link = new Link($relOrLink, $href);
        } else {
            $link = $relOrLink;
        }

        if ($href) {
            $link = $link->withHref($href);
        }

        foreach ($attrs as $name => $value) {
            $link = $link->withAttribute($name, $value);
        }

        return $this->addCustomTag($link);
    }

    public function addHreflang(
        string $hreflang,
        string|Stringable $href,
        array $attrs = [],
    ): DOMElement {
        $link = new Link('alternate');
        $link = $link->withHref($href)
            ->withAttribute('hreflang', $hreflang);

        return $this->addLink($link, attrs: $attrs);
    }

    public function addCanonical(
        string|Stringable $href,
        array $attrs = [],
    ): DOMElement {
        $link = new Link('canonical');
        $link = $link->withHref($href);

        return $this->addLink($link, attrs: $attrs);
    }

    public function setRobotsMeta(
        array|string $content = '',
        $name = 'robots'
    ): static {
        $content = implode(',', (array) $content);

        return $this->addMetadata(
            $name,
            $content,
            true
        );
    }

    public function setNoindex($name = 'robots'): static
    {
        return $this->setRobotsMeta('noindex', $name);
    }

    public function addPreload(
        #[ExpectedValues(
            [
                'audio',
                'document',
                'embed',
                'fetch',
                'font',
                'image',
                'object',
                'script',
                'style',
                'track',
                'worker',
                'video',
            ]
        )]
        string $as,
        string|Stringable $href,
        array $attrs = [],
        ?string $type = null,
        ?string $media = null,
    ): DOMElement {
        $link = (new PreloadLink('preload', $href))
            ->withAs($as);

        if ($type) {
            $link = $link->withType($type);
        }

        if ($media) {
            $link = $link->withMedia($media);
        }

        return $this->addLink($link, attrs: $attrs);
    }

    public static function linkToElement(LinkInterface $link): DOMElement
    {
        $attribs = array_merge(
            [
                'href' => $link->getHref(),
                'rel' => $link->getRels()[0] ?? '',
            ],
            $link->getAttributes()
        );

        return h('link', $attribs);
    }

    /**
     * addCustomTag
     *
     * @param  string|LinkInterface|DOMElement  $tag
     * @param  string|Stringable|null           $content
     * @param  array                            $attribs
     *
     * @return  DOMElement
     */
    public function addCustomTag(
        string|LinkInterface|DOMElement $tag,
        string|Stringable|null $content = null,
        array $attribs = []
    ): DOMElement {
        if ($tag instanceof LinkInterface) {
            $tag = self::linkToElement($tag);
        }

        if (!$tag instanceof DOMElement) {
            $tag = h($tag, $attribs, $content);
        }

        $this->customTags[] = $tag;

        return $tag;
    }

    /**
     * Method to get property CustomTags
     *
     * @return  array<DOMElement>
     */
    public function getCustomTags(): array
    {
        return $this->customTags;
    }

    /**
     * Method to set property customTags
     *
     * @param  array<DOMElement>  $customTags
     *
     * @return  static  Return self to support chaining.
     */
    public function setCustomTags(array $customTags): static
    {
        $this->customTags = $customTags;

        return $this;
    }

    /**
     * addMetadata
     *
     * @param  string             $name
     * @param  string|Stringable  $content
     * @param  bool               $replace
     *
     * @return static
     */
    public function addMetadata(string $name, string|Stringable $content, bool $replace = false): static
    {
        $this->metadata->addMetadata($name, $content, $replace);

        return $this;
    }

    /**
     * addOpenGraph
     *
     * @param  string             $type
     * @param  string|Stringable  $content
     * @param  bool               $replace
     *
     * @return static
     */
    public function addOpenGraph(string $type, string|Stringable $content, bool $replace = false): static
    {
        $this->metadata->addOpenGraph($type, $content, $replace);

        return $this;
    }

    /**
     * renderFavicon
     *
     * @return string|null
     */
    public function renderFavicon(): ?string
    {
        if (!$this->favicon) {
            return null;
        }

        return (string) h(
            'link',
            [
                'rel' => 'shortcut icon',
                'type' => 'image/x-icon',
                'href' => $this->favicon,
            ]
        );
    }

    /**
     * renderTitle
     *
     * @param  string  $separator
     *
     * @return  string
     */
    public function renderTitle(string $separator = '|'): string
    {
        return (string) h('title', [], $this->getPageTitle($separator));
    }

    /**
     * renderMetadata
     *
     * @return  string
     */
    public function renderMetadata(): string
    {
        $html = [];

        foreach ($this->metadata->getMetadata() as $metadata) {
            foreach ($metadata as $item) {
                $html[] = (string) $item;
            }
        }

        foreach ($this->metadata->getOpenGraphs() as $opengraphs) {
            foreach ($opengraphs as $item) {
                $html[] = (string) $item;
            }
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * remnderCustomTags
     *
     * @return  string
     */
    public function renderCustomTags(): string
    {
        $html = [];

        foreach ($this->customTags as $tag) {
            $html[] = (string) $tag;
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * Method to get property SiteName
     *
     * @return string|null
     */
    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    /**
     * Method to set property siteName
     *
     * @param  string|null  $siteName
     *
     * @return  static  Return self to support chaining.
     */
    public function setSiteName(?string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    /**
     * Method to get property Indents
     *
     * @return  string
     */
    public function getIndents(): string
    {
        return $this->indents;
    }

    /**
     * Method to set property indents
     *
     * @param  string  $indents
     *
     * @return  static  Return self to support chaining.
     */
    public function setIndents(string $indents): static
    {
        $this->indents = $indents;

        return $this;
    }

    /**
     * Method to get property Metadata
     *
     * @return  Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Method to set property metadata
     *
     * @param  Metadata  $metadata
     *
     * @return  static  Return self to support chaining.
     */
    public function setMetadata(Metadata $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function setDescription(string $text, ?int $truncate = null): static
    {
        $t = str($text)->stripHtmlTags()->collapseWhitespaces();

        if ($truncate) {
            $t = $t->truncate($truncate, '...');
        }

        $this->addMetadata('description', $text = (string) $t, true);
        $this->addOpenGraph('og:description', $text, true);

        return $this;
    }

    public function setDescriptionIfNotEmpty(?string $text, ?int $truncate = null): static
    {
        if ((string) $text !== '') {
            $this->setDescription($text, $truncate);
        }

        return $this;
    }

    public function setCoverImages(string|Stringable ...$images): static
    {
        $this->getMetadata()->removeOpenGraph('og:image');

        foreach ($images as $image) {
            $image = $this->asset->addAssetBase((string) $image, 'root');

            $this->addOpenGraph('og:image', $image);
        }

        return $this;
    }

    public function setCoverImagesIfNotEmpty(string|Stringable ...$images): static
    {
        $images = array_filter($images);

        if ($images !== []) {
            $this->setCoverImages(...$images);
        }

        return $this;
    }

    /**
     * getBody
     *
     * @return  DOMElement
     */
    public function getBodyElement(): DOMElement
    {
        return $this->body;
    }

    /**
     * @param  DOMElement  $body
     *
     * @return  static  Return self to support chaining.
     */
    public function setBodyElement(DOMElement $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function addBodyClass(string $class): DOMElement
    {
        return $this->body->addClass($class);
    }

    public function bodyAttributes(): string
    {
        return DOMElement::buildAttributes($this->body);
    }

    /**
     * @return DOMElement
     */
    public function getHtmlElement(): DOMElement
    {
        return $this->html;
    }

    /**
     * @param  DOMElement  $html
     *
     * @return  static  Return self to support chaining.
     */
    public function setHtmlElement(DOMElement $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function addHtmlClass(string $class): DOMElement
    {
        return $this->html->addClass($class);
    }

    public function htmlAttributes(): string
    {
        return DOMElement::buildAttributes($this->html);
    }
}
