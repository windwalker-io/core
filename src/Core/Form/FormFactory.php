<?php

declare(strict_types=1);

namespace Windwalker\Core\Form;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Form\Field\AbstractField;
use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\Form;
use Windwalker\Form\FormDefinitionWrapper;
use Windwalker\Form\Renderer\FormRendererInterface;

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
    public function __construct(protected ApplicationInterface $app, protected ?FormRendererInterface $renderer = null)
    {
        //
    }

    public function create(object|string|null $definition = null, ...$args): Form
    {
        $form = $this->app->make(Form::class, $args);

        $form->getObjectBuilder()
            ->setBuilder(fn(string $class, ...$args) => $this->app->make($class, $args));

        if ($definition !== null) {
            if (is_string($definition)) {
                $definition = $this->app->make($definition, $args);
            }

            $form->defineFormFields($definition);
        }

        $form->setRenderer($this->renderer);

        return $form;
    }

    /**
     * createDefinition
     *
     * @param  object|string|null  $definition
     * @param  mixed               ...$args
     *
     * @return  FieldDefinitionInterface
     *
     * @psalm-template T
     */
    public function createDefinition(
        object|string|null $definition = null,
        mixed ...$args
    ): FieldDefinitionInterface {
        $definitionObject =  $this->app->make($definition, $args);

        if (!$definitionObject instanceof FieldDefinitionInterface) {
            $definitionObject = new FormDefinitionWrapper($definitionObject);
        }

        return $definitionObject;
    }

    /**
     * createField
     *
     * @param  string     $fieldClass
     * @param  Form|null  $form
     *
     * @return  AbstractField
     *
     * @psalm-template T
     * @psalm-param T     $fieldClass
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
