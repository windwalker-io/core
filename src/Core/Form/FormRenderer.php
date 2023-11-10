<?php

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\Core\Renderer\RendererService;
use Windwalker\DOM\DOMElement;
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
     * @param  DOMElement     $wrapper
     * @param  array          $options
     *
     * @return string
     */
    public function renderField(AbstractField $field, DOMElement $wrapper, array $options = []): string
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
     * @param  DOMElement     $label
     * @param  array          $options
     *
     * @return string
     */
    public function renderLabel(AbstractField $field, DOMElement $label, array $options = []): string
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
     * @param  DOMElement     $input
     * @param  array          $options
     *
     * @return string
     */
    public function renderInput(AbstractField $field, DOMElement $input, array $options = []): string
    {
        $renderer = $this;

        return $this->rendererService->render(
            '@theme::form.input',
            compact('field', 'input', 'options', 'renderer')
        );
    }
}
