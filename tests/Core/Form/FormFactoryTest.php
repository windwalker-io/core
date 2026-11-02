<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
    public function testCreatePreservesDefaultRendererWhenNoneIsProvided(): void
    {
        $form = new Form();
        $renderer = $form->getRenderer();
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = new FormFactory($app)->create();

        self::assertSame($form, $result);
        self::assertCount(0, $result);
        self::assertSame($renderer, $result->getRenderer());
    }

    public function testCreateUsesProvidedRenderer(): void
    {
        $form = new Form();
        $renderer = $this->createStub(FormRendererInterface::class);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = new FormFactory($app, $renderer)->create();

        self::assertSame($renderer, $result->getRenderer());
    }

    public function testCreateForwardsArgumentsAndBuildsFieldsThroughApplication(): void
    {
        $form = new Form('profile');
        $field = new TextField();
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->exactly(2))
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

        $result = new FormFactory($app)->create(namespace: 'profile');
        $result->add('title', TextField::class);

        self::assertSame($field, $result->getField('title'));
        self::assertSame('title', $field->getName());
        self::assertSame($result, $field->getForm());
    }

    public function testCreateResolvesDefinitionClassWithArguments(): void
    {
        $form = new Form();
        $definition = $this->createMock(FieldDefinitionInterface::class);
        $definition->expects($this->once())->method('define')->with($form);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(
                function (string $class, array $args) use ($form, $definition): object {
                    self::assertSame(['namespace' => 'profile'], $args);
                    self::assertContains($class, [Form::class, FieldDefinitionInterface::class]);

                    return $class === Form::class ? $form : $definition;
                }
            );

        $result = new FormFactory($app)->create(FieldDefinitionInterface::class, namespace: 'profile');

        self::assertSame($form, $result);
    }

    public function testCreateAppliesDefinitionObjectWithoutResolvingIt(): void
    {
        $form = new Form();
        $field = new TextField();
        $definition = static function (Form $form) use ($field): void {
            $form->add('title', $field);
        };
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willReturn($form);

        $result = new FormFactory($app)->create($definition);

        self::assertSame($field, $result->getField('title'));
    }

    public function testCreateDefinitionPreservesFieldDefinitionInterface(): void
    {
        $definition = $this->createStub(FieldDefinitionInterface::class);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(FieldDefinitionInterface::class, ['name' => 'profile'])
            ->willReturn($definition);

        $result = new FormFactory($app)->createDefinition(FieldDefinitionInterface::class, name: 'profile');

        self::assertSame($definition, $result);
    }

    public function testCreateDefinitionWrapsObjectsWithoutFieldDefinitionInterface(): void
    {
        $field = new TextField();
        $definition = static function (Form $form) use ($field): void {
            $form->add('title', $field);
        };
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with('profile.definition', ['name' => 'profile'])
            ->willReturn($definition);

        $result = new FormFactory($app)->createDefinition('profile.definition', name: 'profile');
        $form = new Form();
        $result->define($form);

        self::assertInstanceOf(FormDefinitionWrapper::class, $result);
        self::assertSame($field, $form->getField('title'));
    }

    public function testCreateFieldWithoutForm(): void
    {
        $field = new TextField();
        $originalForm = $field->getForm();
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(TextField::class)
            ->willReturn($field);

        $result = new FormFactory($app)->createField(TextField::class);

        self::assertSame($field, $result);
        self::assertSame($originalForm, $result->getForm());
    }

    public function testCreateFieldAttachesProvidedForm(): void
    {
        $form = new Form();
        $field = new TextField();
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(TextField::class)
            ->willReturn($field);

        $result = new FormFactory($app)->createField(TextField::class, $form);

        self::assertSame($field, $result);
        self::assertSame($form, $result->getForm());
        self::assertCount(0, $form);
    }

    public function testCreatePropagatesApplicationErrors(): void
    {
        $exception = new RuntimeException('Unable to build form.');
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('make')
            ->with(Form::class, [])
            ->willThrowException($exception);

        $this->expectExceptionObject($exception);

        new FormFactory($app)->create();
    }
}
