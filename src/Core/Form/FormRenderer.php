<?php

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\Core\Renderer\RendererService;
use Windwalker\DOM\DOMElement;
use Windwalker\DOM\HTMLElement;
use Windwalker\Form\Field\AbstractField;
use Windwalker\Form\Renderer\FormRendererInterface;

/**
 * The FormRenderer class.
 */
class FormRenderer implements FormRendererInterface
{
    /**
     * FormRenderer constructor.
     *
     * @param  RendererService  $rendererService
     */
    public function __construct(protected RendererService $rendererService)
    {
    }

    /**
     * renderField
     *
     * @param  AbstractField  $field
     * @param  HTMLElement    $wrapper
     * @param  array          $options
     *
     * @return string
     */
    public function renderField(AbstractField $field, HTMLElement $wrapper, array $options = []): string
    {
        $renderer = $this;

        return $this->rendererService->render(
            '@theme::form.field-wrapper',
            compact('field', 'wrapper', 'options', 'renderer')
        );
    }

    /**
     * renderLabel
     *
     * @param  AbstractField  $field
     * @param  HTMLElement    $label
     * @param  array          $options
     *
     * @return string
     */
    public function renderLabel(AbstractField $field, HTMLElement $label, array $options = []): string
    {
        $renderer = $this;

        return $this->rendererService->render(
            '@theme::form.label',
            compact('field', 'label', 'options', 'renderer')
        );
    }

    /**
     * renderInput
     *
     * @param  AbstractField  $field
     * @param  HTMLElement    $input
     * @param  array          $options
     *
     * @return string
     */
    public function renderInput(AbstractField $field, HTMLElement $input, array $options = []): string
    {
        $renderer = $this;

        return $this->rendererService->render(
            '@theme::form.input',
            compact('field', 'input', 'options', 'renderer')
        );
    }
}
