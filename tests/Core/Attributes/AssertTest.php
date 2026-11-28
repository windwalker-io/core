<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Attributes;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Attributes\Assert;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\AttributesResolver;
use Windwalker\DI\Container;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Filter\FilterFactory;
use Windwalker\Filter\Rule\Regex;

#[CoversClass(Assert::class)]
class AssertTest extends TestCase
{
    private function validate(Assert $attribute, mixed $value): mixed
    {
        $container = new Container();
        $container->share(FilterService::class, new FilterService(new FilterFactory()));
        $container->share(ApplicationInterface::class, $this->createStub(ApplicationInterface::class));

        $handler = new AttributeHandler(
            static fn(): mixed => $value,
            new \ReflectionFunction(static fn() => null),
            null,
            new AttributesResolver($container),
            [],
            $container,
        );

        return $attribute($handler)();
    }

    #[Test]
    public function defaultOptions(): void
    {
        $assert = new Assert('int');

        self::assertSame(['int'], $assert->commands);
        self::assertTrue($assert->nullable);
        self::assertFalse($assert->strict);
    }

    #[Test]
    public function positionalBoolsMapToNullableThenStrict(): void
    {
        $assert = new Assert('int', false, true);

        self::assertSame(['int'], $assert->commands);
        self::assertFalse($assert->nullable);
        self::assertTrue($assert->strict);
    }

    #[Test]
    public function singlePositionalBoolMapsToNullable(): void
    {
        $assert = new Assert('int', false);

        self::assertFalse($assert->nullable);
        self::assertFalse($assert->strict);
    }

    #[Test]
    public function namedOptions(): void
    {
        $assert = new Assert('int', nullable: false, strict: true);

        self::assertFalse($assert->nullable);
        self::assertTrue($assert->strict);
    }

    #[Test]
    public function positionalAndNamedOptionsCanMix(): void
    {
        $assert = new Assert('int', false, strict: true);

        self::assertFalse($assert->nullable);
        self::assertTrue($assert->strict);
    }

    #[Test]
    public function closuresAndStringsAreKeptAsCommandsInOrder(): void
    {
        $closure = static fn(mixed $v): bool => $v > 1;
        $assert = new Assert('int', $closure, 'string', false, true);

        self::assertCount(3, $assert->commands);
        self::assertSame('int', $assert->commands[0]);
        self::assertSame($closure, $assert->commands[1]);
        self::assertSame('string', $assert->commands[2]);
        self::assertFalse($assert->nullable);
        self::assertTrue($assert->strict);
    }

    #[Test]
    public function tooManyPositionalBoolsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Assert('int', true, false, true);
    }

    #[Test]
    public function positionalAndNamedSameOptionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Assert('int', false, nullable: true);
    }

    #[Test]
    public function unknownNamedOptionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Assert('int', foo: true);
    }

    #[Test]
    public function nonBoolNamedOptionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Assert('int', strict: 'yes');
    }

    #[Test]
    public function invokeReturnsValueWhenStringCommandPasses(): void
    {
        self::assertSame(5, $this->validate(new Assert('int'), 5));
    }

    #[Test]
    public function invokeThrowsWhenStringCommandFails(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert('int'), 'abc');
    }

    #[Test]
    public function invokeSkipsNullByDefault(): void
    {
        self::assertNull($this->validate(new Assert('int'), null));
    }

    #[Test]
    public function invokeValidatesNullWhenNullableIsFalse(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert('required', nullable: false), null);
    }

    #[Test]
    public function invokeRunsClosureCommand(): void
    {
        $assert = new Assert(static fn(mixed $v): bool => $v > 10);

        self::assertSame(11, $this->validate($assert, 11));
    }

    #[Test]
    public function invokeThrowsWhenClosureReturnsFalsy(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert(static fn(mixed $v): bool => $v > 10), 5);
    }

    #[Test]
    public function invokeAppliesEveryCommandInOrder(): void
    {
        $assert = new Assert('int', static fn(mixed $v): bool => $v < 10);

        self::assertSame(5, $this->validate($assert, 5));

        $this->expectException(ValidateException::class);

        $this->validate($assert, 50);
    }

    #[Test]
    public function invokeUsesStrictFlag(): void
    {
        self::assertSame('5', $this->validate(new Assert('int'), '5'));

        $this->expectException(ValidateException::class);

        $this->validate(new Assert('int', strict: true), '5');
    }

    #[Test]
    public function invokeUsesStrictFlagFromPositionalArgument(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert('int', true, true), '5');
    }

    #[Test]
    public function constructKeepsValidatorInstanceAsCommand(): void
    {
        $regex = new Regex('/^\d+$/');
        $assert = new Assert('required', is_numeric(...), $regex);

        self::assertCount(3, $assert->commands);
        self::assertSame('required', $assert->commands[0]);
        self::assertInstanceOf(\Closure::class, $assert->commands[1]);
        self::assertSame($regex, $assert->commands[2]);
    }

    #[Test]
    public function invokeRunsClosureAndValidatorInstanceCommands(): void
    {
        $assert = new Assert('required', is_numeric(...), new Regex('/^\d+$/'));

        self::assertSame('123', $this->validate($assert, '123'));
    }

    #[Test]
    public function invokeThrowsWhenValidatorInstanceFails(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert('required', is_numeric(...), new Regex('/^\d+$/')), '12a');
    }

    #[Test]
    public function invokeThrowsWhenClosureFailsBeforeValidatorInstance(): void
    {
        $this->expectException(ValidateException::class);

        $this->validate(new Assert('required', is_numeric(...), new Regex('/^\d+$/')), 'abc');
    }
}
