<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Service;

use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Filter\CallbackFilter;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Filter\FilterFactory;
use Windwalker\Filter\FilterInterface;
use Windwalker\Filter\NestedFilter;
use Windwalker\Filter\ValidatorInterface;
use Windwalker\Core\Service\FilterService;

#[CoversClass(FilterService::class)]
class FilterServiceTest extends TestCase
{
    private function createService(): FilterService
    {
        return new FilterService(new FilterFactory());
    }

    #[Test]
    public function filterAppliesSingleSyntaxToScalarValue(): void
    {
        $service = $this->createService();

        self::assertSame(42, $service->filter(' 42 ', 'int'));
    }

    #[Test]
    public function filterAppliesChainedSyntaxInOrder(): void
    {
        $service = $this->createService();

        self::assertSame('abc123', $service->filter(' abc123 !! ', 'cmd|string'));
    }

    #[Test]
    public function filterAppliesNestedMapToArrayValue(): void
    {
        $service = $this->createService();

        $result = $service->filter(
            ['id' => ' 5 ', 'name' => ' foo '],
            ['id' => 'int', 'name' => 'string']
        );

        self::assertSame(['id' => 5, 'name' => ' foo '], $result);
    }

    #[Test]
    public function validateReturnsTrueWhenScalarValuePassesSyntax(): void
    {
        $service = $this->createService();

        self::assertTrue($service->validate('foo@example.com', 'email'));
    }

    #[Test]
    public function validateThrowsValidateExceptionWhenScalarValueFailsSyntax(): void
    {
        $service = $this->createService();

        $this->expectException(ValidateException::class);

        $service->validate('not-an-email', 'email');
    }

    #[Test]
    public function validateReturnsTrueWhenNestedMapValuesPass(): void
    {
        $service = $this->createService();

        self::assertTrue(
            $service->validate(
                ['id' => 5, 'email' => 'foo@example.com'],
                ['id' => 'int', 'email' => 'email']
            )
        );
    }

    #[Test]
    public function validateThrowsValidateExceptionWhenNestedMapValueFails(): void
    {
        $service = $this->createService();

        $this->expectException(ValidateException::class);

        $service->validate(
            ['id' => 5, 'email' => 'not-an-email'],
            ['id' => 'int', 'email' => 'email']
        );
    }

    #[Test]
    public function createNestedReturnsNestedFilterBuiltFromMap(): void
    {
        $service = $this->createService();

        $nested = $service->createNested(['id' => 'int']);

        self::assertInstanceOf(NestedFilter::class, $nested);
        self::assertSame(['id' => 1], $nested->filter(['id' => '1']));
    }

    #[Test]
    public function createChainReturnsFilterBuiltFromSyntax(): void
    {
        $service = $this->createService();

        $chain = $service->createChain('int');

        self::assertSame(7, $chain->filter('7'));
    }

    #[Test]
    public function addFilterFactoryRegistersCustomFactoryUsableBySyntax(): void
    {
        $service = $this->createService();

        $service->addFilterFactory('double', static fn() => new CallbackFilter(static fn($v) => $v * 2));

        self::assertSame(10, $service->filter(5, 'double'));
    }

    #[Test]
    public function addFilterCallbackRegistersCallbackUsableBySyntax(): void
    {
        $service = $this->createService();

        $service->addFilterCallback('upper', static fn($v) => strtoupper((string) $v));

        self::assertSame('FOO', $service->filter('foo', 'upper'));
    }

    #[Test]
    public function removeFilterMakesSyntaxUnavailable(): void
    {
        $service = $this->createService();

        $service->removeFilter('int');

        $this->expectException(OutOfRangeException::class);

        $service->filter('1', 'int');
    }

    #[Test]
    public function methodsReturnServiceInstanceForChaining(): void
    {
        $service = $this->createService();

        self::assertSame($service, $service->addFilterFactory('noop', static fn() => new CallbackFilter(static fn($v) => $v)));
        self::assertSame($service, $service->addFilterCallback('noop2', static fn($v) => $v));
        self::assertSame($service, $service->removeFilter('noop'));
    }

    #[Test]
    public function filterWithAppliesSingleClosureToScalarValue(): void
    {
        $service = $this->createService();

        self::assertSame('1', $service->filterWith(' 1 ', static fn($v) => trim($v)));
    }

    #[Test]
    public function filterWithAppliesClosureListInOrder(): void
    {
        $service = $this->createService();

        self::assertSame('12', $service->filterWith(' 12 ', [strval(...), trim(...)]));
    }

    #[Test]
    public function filterWithAppliesClosureToWholeArrayValue(): void
    {
        $service = $this->createService();

        self::assertSame(3, $service->filterWith([1, 2], [static fn(array $a) => array_sum($a)]));
    }

    #[Test]
    public function filterWithAppliesNestedCallbackMapToArrayValue(): void
    {
        $service = $this->createService();

        $result = $service->filterWith(
            ['foo' => ' 12 ', 'tags' => ['a'], 'nested' => ['n' => '5'], 'extra' => 'keep'],
            [
                'foo' => [strval(...), trim(...)],
                'tags' => static fn(array $t) => array_map(strtoupper(...), $t),
                'nested' => ['n' => static fn($v) => (int) $v],
            ]
        );

        self::assertSame(
            ['foo' => '12', 'tags' => ['A'], 'nested' => ['n' => 5], 'extra' => 'keep'],
            $result
        );
    }

    #[Test]
    public function filterWithSkipsRuleKeysMissingFromValue(): void
    {
        $service = $this->createService();

        $result = $service->filterWith(['foo' => ' a '], ['foo' => trim(...), 'missing' => trim(...)]);

        self::assertSame(['foo' => 'a'], $result);
    }

    #[Test]
    public function filterWithReturnsScalarValueUnchangedForMapRule(): void
    {
        $service = $this->createService();

        self::assertSame('abc', $service->filterWith('abc', ['foo' => trim(...)]));
    }

    #[Test]
    public function validateWithReturnsTrueWhenSingleClosurePasses(): void
    {
        $service = $this->createService();

        self::assertTrue($service->validateWith(5, static fn($v) => $v > 1));
    }

    #[Test]
    public function validateWithReturnsTrueWhenAllChainedClosuresPass(): void
    {
        $service = $this->createService();

        self::assertTrue(
            $service->validateWith('abc', [static fn($v) => is_string($v), static fn($v) => strlen($v) === 3])
        );
    }

    #[Test]
    public function validateWithReturnsTrueWhenNestedMapPasses(): void
    {
        $service = $this->createService();

        self::assertTrue($service->validateWith(['age' => 20], ['age' => static fn(int $a) => $a >= 18]));
    }

    #[Test]
    public function validateWithThrowsWhenChainedClosureFails(): void
    {
        $service = $this->createService();

        $this->expectException(ValidateException::class);
        $this->expectExceptionMessageIsOrContains('Value not match');

        $service->validateWith('ab', [static fn($v) => is_string($v), static fn($v) => strlen($v) === 3]);
    }

    #[Test]
    public function validateWithThrowsWithFieldMessageWhenMapFieldFails(): void
    {
        $service = $this->createService();

        $this->expectException(ValidateException::class);
        $this->expectExceptionMessageIsOrContains('Field "age" not match');

        $service->validateWith(['age' => 10], ['age' => static fn(int $a) => $a >= 18]);
    }

    #[Test]
    public function validateWithUsesDotPathForNestedFailures(): void
    {
        $service = $this->createService();

        $this->expectException(ValidateException::class);
        $this->expectExceptionMessageIsOrContains('Field "a.b" not match');

        $service->validateWith(['a' => ['b' => 0]], ['a' => ['b' => static fn($v) => (bool) $v]]);
    }

    #[Test]
    public function filterWithAcceptsFilterInterfaceInstance(): void
    {
        $service = $this->createService();

        $upper = new class () implements FilterInterface {
            public function filter(mixed $value): mixed
            {
                return strtoupper($value);
            }
        };

        self::assertSame('ABC', $service->filterWith('abc', $upper));
    }

    #[Test]
    public function filterWithChainsFilterInterfaceAndClosure(): void
    {
        $service = $this->createService();

        $trim = new class () implements FilterInterface {
            public function filter(mixed $value): mixed
            {
                return trim($value);
            }
        };

        self::assertSame('ABC', $service->filterWith('  abc ', [$trim, strtoupper(...)]));
    }

    #[Test]
    public function filterWithThrowsWhenItemIsValidatorInterface(): void
    {
        $service = $this->createService();

        $validator = new class () implements ValidatorInterface {
            public function test(mixed $value, bool $strict = false): bool
            {
                return true;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Filter rule must be a Closure or FilterInterface');

        $service->filterWith('abc', [$validator]);
    }

    #[Test]
    public function validateWithAcceptsValidatorInterfaceInstance(): void
    {
        $service = $this->createService();

        $string = new class () implements ValidatorInterface {
            public function test(mixed $value, bool $strict = false): bool
            {
                return is_string($value);
            }
        };

        self::assertTrue($service->validateWith('abc', $string));
    }

    #[Test]
    public function validateWithThrowsWhenValidatorInterfaceFails(): void
    {
        $service = $this->createService();

        $string = new class () implements ValidatorInterface {
            public function test(mixed $value, bool $strict = false): bool
            {
                return is_string($value);
            }
        };

        $this->expectException(ValidateException::class);
        $this->expectExceptionMessageIsOrContains('Value not match');

        $service->validateWith(123, $string);
    }

    #[Test]
    public function validateWithChainsValidatorInterfaceAndClosure(): void
    {
        $service = $this->createService();

        $string = new class () implements ValidatorInterface {
            public function test(mixed $value, bool $strict = false): bool
            {
                return is_string($value);
            }
        };

        self::assertTrue($service->validateWith('abc', [$string, static fn($v) => strlen($v) === 3]));
    }

    #[Test]
    public function validateWithThrowsWhenItemIsFilterInterface(): void
    {
        $service = $this->createService();

        $upper = new class () implements FilterInterface {
            public function filter(mixed $value): mixed
            {
                return strtoupper($value);
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Validate rule must be a Closure or ValidatorInterface');

        $service->validateWith('abc', [$upper]);
    }
}
