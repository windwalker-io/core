<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Html;

use Windwalker\DOM\DOMElement;

use Windwalker\DOM\DOMTokenList;

use Windwalker\DOM\HTMLElement;

use function Windwalker\DOM\h;

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
    public function __construct(Metadata $metadata = null)
    {
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
    public function getFavicon(): string
    {
        return $this->favicon;
    }

    /**
     * Method to set property favicon
     *
     * @param  string  $favicon
     *
     * @return  static  Return self to support chaining.
     */
    public function setFavicon(string $favicon): static
    {
        $this->favicon = $favicon;

        return $this;
    }

    /**
     * addCustomTag
     *
     * @param  string|DOMElement        $tag
     * @param  string|\Stringable|null  $content
     * @param  array                    $attribs
     *
     * @return  static
     */
    public function addCustomTag(string|DOMElement $tag, string|\Stringable|null $content = null, $attribs = [])
    {
        if (!$tag instanceof DOMElement) {
            $tag = h($tag, $attribs, $content);
        }

        $this->customTags[] = $tag;

        return $this;
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
     * @param  array  $customTags
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
     * @param  string              $name
     * @param  string|\Stringable  $content
     * @param  bool                $replace
     *
     * @return static
     */
    public function addMetadata(string $name, string|\Stringable $content, bool $replace = false): static
    {
        $this->metadata->addMetadata($name, $content, $replace);

        return $this;
    }

    /**
     * addOpenGraph
     *
     * @param  string              $type
     * @param  string|\Stringable  $content
     * @param  bool                $replace
     *
     * @return static
     */
    public function addOpenGraph(string $type, string|\Stringable $content, bool $replace = false): static
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
    public function renderMetadata()
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
        return HTMLElement::buildAttributes($this->body);
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
        return HTMLElement::buildAttributes($this->html);
    }
}
