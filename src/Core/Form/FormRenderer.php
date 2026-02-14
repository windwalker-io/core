<?php

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\Core\Renderer\RendererService;
use Windwalker\DOM\HTMLElement;
use Windwalker\Form\Field\AbstractField;
use Windwalker\Form\Renderer\FormRendererInterface;
use Windwalker\Form\Renderer\SimpleRenderer;

/**
 * The FormRenderer class.
 */
class FormRenderer implements FormRendererInterface
{
    public FormRendererInterface $fallbackRenderer;

    public function __construct(protected RendererService $rendererService)
    {
        $this->fallbackRenderer = new SimpleRenderer();
    }

    public function renderField(AbstractField $field, HTMLElement $wrapper, array $options = []): string
    {
        $renderer = $this;

        if (!$this->rendererService->hasLayout('@theme::form.field-wrapper')) {
            return $this->fallbackRenderer->renderField($field, $wrapper, $options);
        }

        return $this->rendererService->render(
            '@theme::form.field-wrapper',
            compact('field', 'wrapper', 'options', 'renderer')
        );
    }

    public function renderLabel(AbstractField $field, HTMLElement $label, array $options = []): string
    {
        $renderer = $this;

        if (!$this->rendererService->hasLayout('@theme::form.label')) {
            return $this->fallbackRenderer->renderLabel($field, $label, $options);
        }

        return $this->rendererService->render(
            '@theme::form.label',
            compact('field', 'label', 'options', 'renderer')
        );
    }

    public function renderInput(AbstractField $field, HTMLElement $input, array $options = []): string
    {
        $renderer = $this;

        if (!$this->rendererService->hasLayout('@theme::form.input')) {
            return $this->fallbackRenderer->renderInput($field, $input, $options);
        }

        return $this->rendererService->render(
            '@theme::form.input',
            compact('field', 'input', 'options', 'renderer')
        );
    }
}
