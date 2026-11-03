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
use Windwalker\Filter\NestedFilter;
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
}
