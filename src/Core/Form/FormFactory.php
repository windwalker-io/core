<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Form\Field\AbstractField;
use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\Form;

/**
 * The FormFactory class.
 */
class FormFactory
{
    /**
     * FormFactory constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  FormRenderer|null     $renderer
     */
    public function __construct(protected ApplicationInterface $app, protected ?FormRenderer $renderer = null)
    {
        //
    }

    public function create(FieldDefinitionInterface|string|null $definition = null, ...$args): Form
    {
        $form = $this->app->make(Form::class, $args);

        $form->getObjectBuilder()
            ->setBuilder(fn(string $class, ...$args) => $this->app->make($class, $args));

        if ($definition !== null) {
            $form->defineFormFields($definition);
        }

        $form->setRenderer($this->renderer);

        return $form;
    }

    /**
     * createDefinition
     *
     * @param  FieldDefinitionInterface|string|null  $definition
     * @param                                        ...$args
     *
     * @return  FieldDefinitionInterface
     *
     * @psalm-template T
     * @psalm-param T $definition
     *
     * @psalm-return T
     */
    public function createDefinition(
        FieldDefinitionInterface|string|null $definition = null,
        ...$args
    ): FieldDefinitionInterface {
        return $this->app->make($definition, $args);
    }

    /**
     * createField
     *
     * @param  string  $fieldClass
     * @param  Form|null  $form
     *
     * @return  AbstractField
     *
     * @psalm-template T
     * @psalm-param T $fieldClass
     *
     * @psalm-return T
     */
    public function createField(string $fieldClass, ?Form $form = null): AbstractField
    {
        /** @var AbstractField $field */
        $field = $this->app->make($fieldClass);

        if ($form) {
            $field->setForm($form);
        }

        return $field;
    }
}
