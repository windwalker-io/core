<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Seed;

use Faker\Generator as FakerGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Seed\FakerService;

#[CoversClass(FakerService::class)]
class FakerServiceTest extends TestCase
{
    #[Test]
    public function createReturnsFakerGeneratorForDefaultLocale(): void
    {
        $instance = new FakerService();

        $faker = $instance->create();

        self::assertInstanceOf(FakerGenerator::class, $faker);
    }

    #[Test]
    public function createNormalizesDashLocaleToUnderscoreLocale(): void
    {
        $instance = new FakerService();

        $dashLocale = $instance->create('fr-FR');
        $underscoreLocale = $instance->create('fr_FR');

        $dashProviders = array_map(get_class(...), $dashLocale->getProviders());
        $underscoreProviders = array_map(get_class(...), $underscoreLocale->getProviders());

        self::assertSame($underscoreProviders, $dashProviders);
        self::assertContains('Faker\\Provider\\fr_FR\\Address', $dashProviders);
    }

    #[Test]
    public function createEmitsFakerCreatedEventAndAllowsReplacingTheGenerator(): void
    {
        $instance = new FakerService();
        $replacement = new FakerGenerator();

        $instance->on(
            'faker.created',
            static function ($event) use ($replacement) {
                $event['faker'] = $replacement;
            }
        );

        $faker = $instance->create();

        self::assertSame($replacement, $faker);
    }
}
