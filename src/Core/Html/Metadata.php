<?php

/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Html;

use Stringable;
use Windwalker\DOM\DOMElement;

use function Windwalker\DOM\h;

/**
 * The Metadata class.
 *
 * @since  1.1
 */
class Metadata
{
    /**
     * Property metadata.
     *
     * @var  array
     */
    protected array $metadata = [];

    /**
     * Property openGraphs.
     *
     * @var  array
     */
    protected array $openGraphs = [];

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
        if (!isset($this->metadata[$name]) || $replace) {
            $this->metadata[$name] = [];
        }

        if (is_stringable($content)) {
            $content = (string) $content;
        }

        foreach ((array) $content as $item) {
            $this->metadata[$name][] = $this->createMetadataTag($name, $item);
        }

        return $this;
    }

    /**
     * @param  string  $name
     * @param  mixed   $content
     *
     * @return  DOMElement
     */
    protected function createMetadataTag(string $name, mixed $content): DOMElement
    {
        return h('meta', [
            'name' => $this->escape($name),
            'content' => $this->escape($content),
        ]);
    }

    /**
     * removeMetadata
     *
     * @param  string  $name
     *
     * @return  static
     */
    public function removeMetadata(string $name): static
    {
        if (isset($this->metadata[$name])) {
            unset($this->metadata[$name]);
        }

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
    public function addOpenGraph(string $type, string|Stringable $content, bool $replace = false)
    {
        if (!isset($this->openGraphs[$type]) || $replace) {
            $this->openGraphs[$type] = [];
        }

        if (is_stringable($content)) {
            $content = (string) $content;
        }

        foreach ((array) $content as $item) {
            $this->openGraphs[$type][] = $this->createOpenGraphTag($type, $item);
        }

        return $this;
    }

    public function createOpenGraphTag(string $type, string $content): DOMElement
    {
        return h('meta', [
            'property' => $this->escape($type),
            'content' => $this->escape($content),
        ]);
    }

    /**
     * removeOpenGraph
     *
     * @param  string  $type
     *
     * @return  static
     */
    public function removeOpenGraph(string $type): static
    {
        if (isset($this->openGraphs[$type])) {
            unset($this->openGraphs[$type]);
        }

        return $this;
    }

    /**
     * Method to get property Metadata
     *
     * @return  array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Method to set property metadata
     *
     * @param  array  $metadata
     *
     * @return  static  Return self to support chaining.
     */
    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Method to get property OpenGraphs
     *
     * @return  array
     */
    public function getOpenGraphs(): array
    {
        return $this->openGraphs;
    }

    /**
     * Method to set property openGraphs
     *
     * @param  array  $openGraphs
     *
     * @return  static  Return self to support chaining.
     */
    public function setOpenGraphs(array $openGraphs): static
    {
        $this->openGraphs = $openGraphs;

        return $this;
    }

    /**
     * escape
     *
     * @param  string  $string
     *
     * @return  string
     */
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
    }
}
