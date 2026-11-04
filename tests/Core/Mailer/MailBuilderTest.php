<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Mailer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Mailer\MailBuilder;
use Windwalker\DOM\HTMLElement;
use Windwalker\Html\Grid\Grid;

#[CoversClass(MailBuilder::class)]
class MailBuilderTest extends TestCase
{
    #[Test]
    public function lineAddsParagraphBlock(): void
    {
        $builder = new MailBuilder();

        $builder->line('Hello World');

        self::assertCount(1, $builder->blocks);
        self::assertStringContainsString('Hello World', (string) $builder);
        self::assertStringContainsString('<p', (string) $builder);
    }

    #[Test]
    public function lineAlignCenterAddsAlignClass(): void
    {
        $builder = new MailBuilder();

        $builder->lineAlignCenter('Center');

        self::assertStringContainsString('text-center align-center', (string) $builder);
    }

    #[Test]
    public function lineAlignEndAddsAlignClass(): void
    {
        $builder = new MailBuilder();

        $builder->lineAlignEnd('End');

        self::assertStringContainsString('text-right align-right', (string) $builder);
    }

    #[Test]
    public function addLineThrowsExceptionIfAlreadyInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('You cannot add more line in a line.');

        $builder->addLine('foo', []);
    }

    #[Test]
    public function addLineAcceptsClosureAsSubBuilder(): void
    {
        $builder = new MailBuilder();

        $element = $builder->addLine(
            function (MailBuilder $sub) {
                $sub->text('one');
                $sub->text('two');

                return $sub;
            },
            []
        );

        self::assertInstanceOf(HTMLElement::class, $element);
        self::assertStringContainsString('one', (string) $element);
        self::assertStringContainsString('two', (string) $element);
    }

    #[Test]
    public function addLineAcceptsArrayOfElements(): void
    {
        $builder = new MailBuilder();

        $element = $builder->addLine(['one', 'two'], []);

        self::assertStringContainsString('one', (string) $element);
        self::assertStringContainsString('two', (string) $element);
    }

    #[Test]
    public function emptyLineAddsGivenCountOfBlankLines(): void
    {
        $builder = new MailBuilder();

        $builder->emptyLine(2);

        self::assertCount(2, $builder->blocks);
    }

    #[Test]
    public function linebreakAddsLineWrapperWhenNotInline(): void
    {
        $builder = new MailBuilder();

        $builder->linebreak();

        self::assertCount(1, $builder->blocks);
        self::assertStringContainsString('<br', (string) $builder);
    }

    #[Test]
    public function linebreakAddsBrTagsDirectlyWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $builder->linebreak(2);

        self::assertCount(2, $builder->blocks);
        self::assertStringContainsString('<br', (string) $builder);
    }

    #[Test]
    public function buttonAddsLinkWithoutExtraClass(): void
    {
        $builder = new MailBuilder();

        $builder->button('Click', 'https://example.com');

        $html = (string) $builder;

        self::assertStringContainsString('Click', $html);
        self::assertStringContainsString('href="https://example.com"', $html);
        self::assertStringContainsString('btn', $html);
    }

    #[Test]
    public function primaryButtonAddsPrimaryClass(): void
    {
        $builder = new MailBuilder();

        $builder->primaryButton('Click', 'https://example.com');

        self::assertStringContainsString('btn-primary', (string) $builder);
    }

    #[Test]
    public function secondaryButtonAddsSecondaryClass(): void
    {
        $builder = new MailBuilder();

        $builder->secondaryButton('Click', 'https://example.com');

        self::assertStringContainsString('btn-secondary', (string) $builder);
    }

    #[Test]
    public function dangerButtonAddsDangerClass(): void
    {
        $builder = new MailBuilder();

        $builder->dangerButton('Click', 'https://example.com');

        self::assertStringContainsString('btn-danger', (string) $builder);
    }

    #[Test]
    public function addButtonReturnsLinkElementWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $link = $builder->addButton('Click', 'https://example.com');

        self::assertInstanceOf(HTMLElement::class, $link);
        self::assertStringContainsString('Click', (string) $link);
    }

    #[Test]
    public function tableBuildsGridFromArrayRows(): void
    {
        $builder = new MailBuilder();

        $builder->table(
            [
                ['name' => 'Name', 'age' => 'Age'],
                ['name' => 'Foo', 'age' => '18'],
            ]
        );

        $html = (string) $builder;

        self::assertStringContainsString('Name', $html);
        self::assertStringContainsString('Foo', $html);
        self::assertStringContainsString('18', $html);
    }

    #[Test]
    public function tableAcceptsGridClosure(): void
    {
        $builder = new MailBuilder();

        $builder->table(
            function (Grid $grid) {
                $grid->addColumn('name');
                $grid->addRow();
                $grid->setRowCell('name', 'Foo');

                return $grid;
            }
        );

        self::assertStringContainsString('Foo', (string) $builder);
    }

    #[Test]
    public function tableAcceptsGridInstanceDirectly(): void
    {
        $grid = new Grid();
        $grid->addRow();
        $grid->setRowCell('name', 'Foo');

        $builder = new MailBuilder();

        $builder->table($grid);

        self::assertSame($grid, $builder->blocks[0]);
    }

    #[Test]
    public function tableThrowsExceptionWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('You cannot add table in a line.');

        $builder->table([]);
    }

    #[Test]
    public function blockRendersMultilineGitHubFlavoredMarkdownSafely(): void
    {
        $builder = new MailBuilder();

        $builder->block(
            "~~removed~~\n\n| Name |\n| --- |\n| Earth |\n\n[unsafe](javascript:alert(1))\n\n<script>alert(1)</script>"
        );

        $html = (string) $builder;

        self::assertStringContainsString('<del>removed</del>', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringNotContainsString('href="javascript:', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    #[Test]
    public function blockThrowsExceptionWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('You cannot add block in a line.');

        $builder->block("one\n\ntwo");
    }

    #[Test]
    public function textAddsLineWhenNotInline(): void
    {
        $builder = new MailBuilder();

        $builder->text('Hello');

        self::assertCount(1, $builder->blocks);
        self::assertStringContainsString('<p', (string) $builder);
        self::assertStringContainsString('Hello', (string) $builder);
    }

    #[Test]
    public function textAddsRawTextWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $builder->text('Hello');

        self::assertSame(['Hello'], $builder->blocks);
    }

    #[Test]
    public function textEscapesHtmlWhenInline(): void
    {
        $builder = new MailBuilder(inline: true);

        $builder->text('<script>alert("x")</script>');

        self::assertSame(['&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;'], $builder->blocks);
        self::assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', (string) $builder);
    }

    #[Test]
    public function rawAddsRawContentDirectly(): void
    {
        $builder = new MailBuilder();

        $builder->raw('<hr>');

        self::assertSame(['<hr>'], $builder->blocks);
        self::assertStringContainsString('<hr>', (string) $builder);
    }

    #[Test]
    public function tryRenderMarkdownKeepsPlainTextWhenMarkdownDisabled(): void
    {
        $builder = new MailBuilder(inline: true);

        $builder->text('**bold**');

        self::assertSame(['**bold**'], $builder->blocks);
    }

    #[Test]
    public function textRendersGitHubFlavoredMarkdownInlineSafely(): void
    {
        $builder = new MailBuilder(markdown: true, inline: true);

        $builder->text('~~removed~~ <script>alert(1)</script>');

        self::assertSame(
            ['<del>removed</del> &lt;script&gt;alert(1)&lt;/script&gt;'],
            $builder->blocks
        );
    }

    #[Test]
    public function toStringImplodesBlocksWithNewline(): void
    {
        $builder = new MailBuilder(inline: true);

        $builder->raw('foo');
        $builder->raw('bar');

        self::assertSame("foo\nbar", (string) $builder);
    }

    #[Test]
    public function useMarkdownTogglesFlag(): void
    {
        $builder = new MailBuilder();

        $builder->useMarkdown();

        self::assertTrue($builder->markdown);

        $builder->useMarkdown(false);

        self::assertFalse($builder->markdown);
    }
}
