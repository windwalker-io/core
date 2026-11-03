<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DI\TaggingFactory;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\DI\Stub\StubCreatedService;

class TaggingFactoryTest extends TestCase
{
    protected function createFactory(array $data = []): TaggingFactory
    {
        return new TaggingFactory(new Config($data), new Container());
    }

    #[Test]
    public function getDefaultNameReturnsFallbackWhenNoDefaultConfigKeySet(): void
    {
        $factory = $this->createFactory()->fallback('bar');

        self::assertSame('bar', $factory->getDefaultName());
    }

    #[Test]
    public function getDefaultNameReturnsConfigValueWhenDefaultConfigKeySet(): void
    {
        $factory = $this->createFactory(['default' => 'foo'])
            ->defaultConfigKey('default')
            ->fallback('bar');

        self::assertSame('foo', $factory->getDefaultName());
    }

    #[Test]
    public function getDefaultNameReturnsFallbackWhenConfigValueMissing(): void
    {
        $factory = $this->createFactory()
            ->defaultConfigKey('default')
            ->fallback('bar');

        self::assertSame('bar', $factory->getDefaultName());
    }

    #[Test]
    public function useConfigReparentsConfigToNewPrefix(): void
    {
        $factory = $this->createFactory(
            [
                'error' => [
                    'default' => 'main',
                ],
            ]
        );

        $returned = $factory->useConfig('error');

        self::assertSame($factory, $returned);
        self::assertSame('error', $factory->getConfigPrefix());
        self::assertSame('main', $factory->config('default'));
    }

    #[Test]
    public function createResolvesDefinitionUnderIdAndConfigPrefix(): void
    {
        $factory = $this->createFactory(
            [
                'error' => [
                    'default' => 'main',
                    'factories' => [
                        'handlers' => [
                            'main' => StubCreatedService::class,
                        ],
                    ],
                ],
            ]
        )
            ->useConfig('error')
            ->id('handlers')
            ->defaultConfigKey('default');

        $instance = $factory->get('main');

        self::assertInstanceOf(StubCreatedService::class, $instance);
        self::assertSame('main', $instance->instanceName);
    }

    #[Test]
    public function createFallsBackToDefaultNameDefinitionWhenRequestedNameHasNoDefinition(): void
    {
        $factory = $this->createFactory(
            [
                'error' => [
                    'default' => 'main',
                    'factories' => [
                        'handlers' => [
                            'main' => StubCreatedService::class,
                        ],
                    ],
                ],
            ]
        )
            ->useConfig('error')
            ->id('handlers')
            ->defaultConfigKey('default');

        // "other" has no own definition, so getDefaultFactory() falls back to
        // the default name's ("main") definition under the same id path.
        $instance = $factory->create('other');

        self::assertInstanceOf(StubCreatedService::class, $instance);
    }

    #[Test]
    public function createThrowsExceptionWhenNoDefinitionAndNoFallbackMatches(): void
    {
        $factory = $this->createFactory()
            ->useConfig('error')
            ->id('handlers');

        self::expectException(\InvalidArgumentException::class);

        $factory->create('other');
    }

    #[Test]
    public function idAndFallbackSettersReturnFactoryForChaining(): void
    {
        $factory = $this->createFactory();

        self::assertSame($factory, $factory->id('handlers'));
        self::assertSame('handlers', $factory->getId());

        self::assertSame($factory, $factory->fallback('bar'));
        self::assertSame('bar', $factory->getFallback());

        self::assertSame($factory, $factory->defaultConfigKey('default'));
        self::assertSame('default', $factory->getDefaultConfigKey());
    }
}
