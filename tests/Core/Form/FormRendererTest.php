<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Form\FormRenderer;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DOM\HTMLElement;
use Windwalker\Form\Field\TextField;

#[CoversClass(FormRenderer::class)]
class FormRendererTest extends TestCase
{
    #[Test]
    public function renderFieldUsesThemeLayoutWhenAvailable(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->expects($this->atLeastOnce())
            ->method('hasLayout')
            ->with('@theme::form.field-wrapper')
            ->willReturn(true);
        $service->expects($this->once())
            ->method('render')
            ->with(
                '@theme::form.field-wrapper',
                self::callback(
                    static fn(
                        array $data
                    ): bool => isset($data['field'], $data['wrapper'], $data['options'], $data['renderer'])
                        && $data['renderer'] instanceof FormRenderer
                )
            )
            ->willReturn('THEME-WRAPPER');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        self::assertSame(
            'THEME-WRAPPER',
            $renderer->renderField($field, HTMLElement::new('div'), ['foo' => 'bar', 'no_label' => false])
        );
    }

    #[Test]
    public function renderLabelUsesThemeLayoutWhenAvailable(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->expects($this->atLeastOnce())
            ->method('hasLayout')
            ->with('@theme::form.label')
            ->willReturn(true);
        $service->expects($this->once())
            ->method('render')
            ->with(
                '@theme::form.label',
                self::callback(
                    static fn(
                        array $data
                    ): bool => isset($data['field'], $data['label'], $data['options'], $data['renderer'])
                )
            )
            ->willReturn('THEME-LABEL');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        self::assertSame(
            'THEME-LABEL',
            $renderer->renderLabel($field, HTMLElement::new('label'))
        );
    }

    #[Test]
    public function renderInputUsesThemeLayoutWhenAvailable(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->expects($this->atLeastOnce())
            ->method('hasLayout')
            ->with('@theme::form.input')
            ->willReturn(true);
        $service->expects($this->once())
            ->method('render')
            ->with(
                '@theme::form.input',
                self::callback(
                    static fn(
                        array $data
                    ): bool => isset($data['field'], $data['input'], $data['options'], $data['renderer'])
                )
            )
            ->willReturn('THEME-INPUT');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        self::assertSame(
            'THEME-INPUT',
            $renderer->renderInput($field, HTMLElement::new('input'))
        );
    }

    #[Test]
    public function renderFieldFallsBackWhenThemeLayoutMissing(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->method('hasLayout')->willReturn(false);
        $service->expects($this->never())->method('render');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        $html = $renderer->renderField($field, HTMLElement::new('div'), ['no_label' => false]);

        self::assertIsString($html);
        self::assertNotSame('', $html);
        self::assertStringContainsString('title', $html);
    }

    #[Test]
    public function renderLabelFallsBackWhenThemeLayoutMissing(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->method('hasLayout')->willReturn(false);
        $service->expects($this->never())->method('render');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        $html = $renderer->renderLabel($field, HTMLElement::new('label'));

        self::assertSame((string) $field->compileLabelElement(HTMLElement::new('label')), $html);
    }

    #[Test]
    public function renderInputFallsBackWhenThemeLayoutMissing(): void
    {
        $service = $this->createMock(RendererService::class);
        $service->method('hasLayout')->willReturn(false);
        $service->expects($this->never())->method('render');

        $renderer = new FormRenderer($service);
        $field = new TextField('title', 'Title');

        $html = $renderer->renderInput($field, HTMLElement::new('input'));

        self::assertSame((string) $field->compileFieldElement(HTMLElement::new('input')), $html);
    }
}
