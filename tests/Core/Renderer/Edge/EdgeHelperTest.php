<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Renderer\Edge;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Renderer\Edge\EdgeHelper;

#[CoversClass(EdgeHelper::class)]
class EdgeHelperTest extends TestCase
{
    #[Test]
    public function attrBuildsSingleAttributeFromStringNameAndValue(): void
    {
        self::assertSame('class="btn"', EdgeHelper::attr('class', 'btn'));
    }

    #[Test]
    public function attrBuildsMultipleAttributesFromArrayName(): void
    {
        self::assertSame(
            'class="btn" data-id="5"',
            EdgeHelper::attr(['class' => 'btn', 'data-id' => 5])
        );
    }

    #[Test]
    public function attrRendersBooleanTrueValueAsEmptyAttribute(): void
    {
        self::assertSame('disabled=""', EdgeHelper::attr('disabled', true));
    }

    #[Test]
    public function attrRendersBooleanFalseValueAsEmptyString(): void
    {
        self::assertSame('', EdgeHelper::attr('disabled', false));
    }

    #[Test]
    public function attrRendersNullValueAsEmptyString(): void
    {
        self::assertSame('', EdgeHelper::attr('name', null));
    }
}
