<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Form;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Form\FormFactory;
use Windwalker\Form\Field\TextField;
use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\Form;
use Windwalker\Form\FormDefinitionWrapper;
use Windwalker\Form\Renderer\FormRendererInterface;

#[CoversClass(FormFactory::class)]
class FormFactoryTest extends TestCase
{
    private ApplicationInterface&MockObject $app;

    private FormFactory $factory;

    #[Before]
    protected function prepareFactory(): void
    {
        $this->app = $this->createMock(ApplicationInterface::class);
        $this->factory = new FormFactory($this->app);
    }

    #[Test]
    public function createPreservesDefaultRendererWhenNoneIsProvided(): void
    {
        $form = new Form();
        $renderer = $form->getRenderer();
        $this->app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = $this->factory->create();

        self::assertSame($form, $result);
        self::assertCount(0, $result);
        self::assertSame($renderer, $result->getRenderer());
    }

    #[Test]
    public function createUsesProvidedRenderer(): void
    {
        $form = new Form();
        $renderer = $this->createStub(FormRendererInterface::class);
        $this->app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = new FormFactory($this->app, $renderer)->create();

        self::assertSame($renderer, $result->getRenderer());
    }

    #[Test]
    public function createForwardsArgumentsAndBuildsFieldsThroughApplication(): void
    {
        $form = new Form('profile');
        $field = new TextField();
        $this->app->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(
                function (string $class, array $args) use ($form, $field): object {
                    if ($class === Form::class) {
                        self::assertSame(['namespace' => 'profile'], $args);

                        return $form;
                    }

                    self::assertSame(TextField::class, $class);
                    self::assertSame(['title'], $args);

                    return $field;
                }
            );

        $result = $this->factory->create(namespace: 'profile');
        $result->add('title', TextField::class);

        self::assertSame($field, $result->getField('title'));
        self::assertSame('title', $field->getName());
        self::assertSame($result, $field->getForm());
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function createResolvesDefinitionClassWithArguments(bool $withRenderer): void
    {
        $form = new Form();
        $renderer = $withRenderer ? $this->createStub(FormRendererInterface::class) : null;
        $expectedRenderer = $renderer ?? $form->getRenderer();
        $definition = $this->createMock(FieldDefinitionInterface::class);
        $definition->expects($this->once())->method('define')->with($form);
        $this->app->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(
                function (string $class, array $args) use ($form, $definition): object {
                    self::assertSame(['namespace' => 'profile'], $args);
                    self::assertContains($class, [Form::class, FieldDefinitionInterface::class]);

                    return $class === Form::class ? $form : $definition;
                }
            );

        $result = new FormFactory($this->app, $renderer)
            ->create(FieldDefinitionInterface::class, namespace: 'profile');

        self::assertSame($form, $result);
        self::assertSame($expectedRenderer, $result->getRenderer());
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function createAppliesDefinitionObjectWithoutResolvingIt(bool $withRenderer): void
    {
        $form = new Form();
        $renderer = $withRenderer ? $this->createStub(FormRendererInterface::class) : null;
        $expectedRenderer = $renderer ?? $form->getRenderer();
        $field = new TextField();
        $definition = static function (Form $form) use ($field): void {
            $form->add('title', $field);
        };
        $this->app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = new FormFactory($this->app, $renderer)->create($definition);

        self::assertSame($field, $result->getField('title'));
        self::assertSame($expectedRenderer, $result->getRenderer());
    }

    #[Test]
    public function createDefinitionPreservesFieldDefinitionInterface(): void
    {
        $definition = $this->createStub(FieldDefinitionInterface::class);
        $this->app->expects($this->once())
            ->method('make')
            ->with(FieldDefinitionInterface::class, ['name' => 'profile'])
            ->willReturn($definition);

        $result = $this->factory->createDefinition(FieldDefinitionInterface::class, name: 'profile');

        self::assertSame($definition, $result);
    }

    #[Test]
    public function createDefinitionWrapsObjectsWithoutFieldDefinitionInterface(): void
    {
        $field = new TextField();
        $definition = static function (Form $form) use ($field): void {
            $form->add('title', $field);
        };
        $this->app->expects($this->once())
            ->method('make')
            ->with('profile.definition', ['name' => 'profile'])
            ->willReturn($definition);

        $result = $this->factory->createDefinition('profile.definition', name: 'profile');
        $form = new Form();
        $result->define($form);

        self::assertInstanceOf(FormDefinitionWrapper::class, $result);
        self::assertSame($field, $form->getField('title'));
    }

    #[Test]
    public function createFieldPreservesExistingFormWhenNoneIsProvided(): void
    {
        $field = new TextField();
        $originalForm = $field->getForm();
        $this->app->expects($this->once())
            ->method('make')
            ->with(TextField::class)
            ->willReturn($field);

        $result = $this->factory->createField(TextField::class);

        self::assertSame($field, $result);
        self::assertSame($originalForm, $result->getForm());
    }

    #[Test]
    public function createFieldAttachesProvidedForm(): void
    {
        $form = new Form();
        $field = new TextField();
        $this->app->expects($this->once())
            ->method('make')
            ->with(TextField::class)
            ->willReturn($field);

        $result = $this->factory->createField(TextField::class, $form);

        self::assertSame($field, $result);
        self::assertSame($form, $result->getForm());
        self::assertCount(0, $form);
    }
}
