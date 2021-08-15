<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\DOM\DOMElement;
use Windwalker\Form\Contract\InputOptionsInterface;
use Windwalker\Form\Field\AbstractField;

/**
 * The BootstrapFormRenderer class.
 */
class BootstrapFormRenderer extends FormRenderer
{
    public static function handleFieldConfiguration(AbstractField $field): void
    {
        if ($field instanceof InputOptionsInterface) {
            $field->setOptionWrapperHandler(
                function (DOMElement $wrapper) {
                    $wrapper->addClass('form-check');
                }
            );
            $field->setOptionHandler(
                function (DOMElement $wrapper) {
                    $wrapper->addClass('form-check-input');
                }
            );
            $field->setOptionLabelHandler(
                function (DOMElement $wrapper) {
                    $wrapper->addClass('form-check-label');
                }
            );
        }
    }

    public static function handleInputClasses(AbstractField $field, DOMElement $inputElement)
    {
        $inputElement->addClass(
            match (true) {
                $inputElement->getAttribute('type') === 'checkbox' => 'form-input-check',
                $field instanceof InputOptionsInterface => '',
                $field->getName() === 'select' => 'custom-select form-select',
                default => 'form-control'
            }
        );
    }
}
