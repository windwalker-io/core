<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Util\HtmlFilter;
use Windwalker\DOM\HTMLElement;
use Windwalker\Html\Grid\Grid;
use Windwalker\Utilities\Classes\ChainingTrait;

use function Windwalker\DOM\h;

class MailBuilder implements \Stringable
{
    use ChainingTrait;

    public array $blocks = [];

    protected ?GithubFlavoredMarkdownConverter $markdownRenderer = null;

    public function __construct(public bool $markdown = false, public bool $inline = false)
    {
        //
    }

    public function useMarkdown(bool $enable = true): static
    {
        $this->markdown = $enable;

        return $this;
    }

    public function line(string|array|\Stringable|\Closure $elements = '', array|\Closure $attrs = []): static
    {
        $this->blocks[] = $this->addLine($elements, $attrs);

        return $this;
    }

    public function lineAlignCenter(
        string|array|\Stringable|\Closure $elements = '',
        array|\Closure $attrs = []
    ): static {
        $this->blocks[] = $this->addLine($elements, $attrs)->addClass('text-center align-center');

        return $this;
    }

    public function lineAlignEnd(string|array|\Stringable|\Closure $elements = '', array|\Closure $attrs = []): static
    {
        $this->blocks[] = $this->addLine($elements, $attrs)->addClass('text-right align-right');

        return $this;
    }

    public function addLine(\Stringable|array|\Closure|string $elements, array|\Closure $attrs): HTMLElement
    {
        if ($this->inline) {
            throw new \LogicException('You cannot add more line in a line.');
        }

        if ($elements instanceof \Closure) {
            $sub = new static($this->markdown, true);
            $sub = $elements($sub) ?? $sub;

            $elements = $sub->blocks;
        }

        if (is_string($elements)) {
            $elements = [$elements];
        }

        $newElements = [];

        foreach (array_values($elements) as $i => $element) {
            if (is_string($element)) {
                $element = $this->tryRenderMarkdown($element);
            }

            if ($i > 0) {
                $newElements[] = "\n";
            }

            $newElements[] = $element;
        }

        return h('p', $attrs, $newElements);
    }

    public function emptyLine(int $count = 1): static
    {
        foreach (range(1, $count) as $i) {
            $this->line(' ');
        }

        return $this;
    }

    public function linebreak(int $count = 1): static
    {
        if (!$this->inline) {
            return $this->line(
                fn(MailBuilder $builder) => $builder->linebreak($count)
            );
        }

        foreach (range(1, $count) as $i) {
            $this->blocks[] = h('br');
        }

        return $this;
    }

    public function button(string|\Stringable $text, string|\Stringable $url, array|\Closure $attrs = []): static
    {
        $this->addButton($text, $url, $attrs);

        return $this;
    }

    public function primaryButton(string|\Stringable $text, string|\Stringable $url, array|\Closure $attrs = []): static
    {
        $this->addButton($text, $url, $attrs)->addClass('btn-primary');

        return $this;
    }

    public function secondaryButton(
        string|\Stringable $text,
        string|\Stringable $url,
        array|\Closure $attrs = []
    ): static {
        $this->addButton($text, $url, $attrs)->addClass('btn-secondary');

        return $this;
    }

    public function dangerButton(string|\Stringable $text, string|\Stringable $url, array|\Closure $attrs = []): static
    {
        $this->addButton($text, $url, $attrs)->addClass('btn-danger');

        return $this;
    }

    public function addButton(
        string|\Stringable $text,
        string|\Stringable $url,
        array|\Closure $attrs = []
    ): HTMLElement {
        if (!$this->inline) {
            $button = null;
            $this->line(
                function (MailBuilder $builder) use ($text, $url, $attrs, &$button) {
                    $button = $builder->addButton($text, $url, $attrs);

                    return $builder;
                }
            );

            return $button;
        }

        $link = h('a', $attrs, $this->tryRenderMarkdown($text));

        $link->setAttribute('href', $url);
        $link->setAttribute('target', '_blank');
        $link->addClass('btn');

        $this->blocks[] = $link;

        return $link;
    }

    public function table(string|\Stringable|array|Grid|\Closure $rows, array $attrs = []): static
    {
        if ($this->inline) {
            throw new \LogicException('You cannot add table in a line.');
        }

        if ($rows instanceof \Closure) {
            $grid = new Grid($attrs);
            $grid = $rows($grid) ?? $grid;
        } elseif (is_array($rows)) {
            $grid = new Grid($attrs);

            if ($rows[0] ?? null) {
                $grid->setColumns(array_keys($rows[0]));
            }

            foreach ($rows as $i => $row) {
                $grid->addRow(
                    special: $i === 0 ? Grid::ROW_HEAD : Grid::ROW_NORMAL
                );

                foreach ($row as $key => $value) {
                    $grid->setRowCell((string) $key, $value);
                }
            }
        } else {
            $grid = $rows;
        }

        $this->blocks[] = $grid;

        return $this;
    }

    public function text(string|\Stringable $text): static
    {
        if (!$this->inline) {
            return $this->line($text);
        }

        $text = (string) $text;
        $this->blocks[] = $this->markdown
            ? $this->tryRenderMarkdown($text)
            : htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return $this;
    }

    public function block(string|\Stringable $text): static
    {
        if ($this->inline) {
            throw new \LogicException('You cannot add block in a line.');
        }

        $this->blocks[] = $this->getMarkdownRenderer()->convert((string) $text);

        return $this;
    }

    public function raw(string|\Stringable $raw): static
    {
        $this->blocks[] = $raw;

        return $this;
    }

    protected function tryRenderMarkdown(mixed $text): mixed
    {
        if (!$this->markdown) {
            return $text;
        }

        $html = (string) $this->getMarkdownRenderer()->convert((string) $text);

        if (preg_match('/^<p>(.*)<\/p>\n?$/sD', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    protected function getMarkdownRenderer(): GithubFlavoredMarkdownConverter
    {
        if (!class_exists(GithubFlavoredMarkdownConverter::class)) {
            throw new \LogicException('Please install league/commonmark ^2.10 first to use Markdown rendering.');
        }

        return $this->markdownRenderer ??= new GithubFlavoredMarkdownConverter(
            [
                'html_input' => HtmlFilter::ESCAPE,
                'allow_unsafe_links' => false,
            ]
        );
    }

    public function __toString(): string
    {
        return implode("\n", $this->blocks);
    }
}
