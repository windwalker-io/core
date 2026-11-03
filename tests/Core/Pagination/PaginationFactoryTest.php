<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Pagination;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Pagination\PaginationFactory;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

class PaginationFactoryTest extends TestCase
{
    protected PaginationFactory $instance;

    #[Test]
    public function create_buildsPaginationWithGivenArguments(): void
    {
        $pagination = $this->instance->create(3, 20, 5);

        self::assertSame(3, $pagination->current);
        self::assertSame(20, $pagination->limit);
        self::assertSame(5, $pagination->neighbours);
    }

    #[Test]
    public function create_fallsBackToDefaultNeighboursWhenNotProvided(): void
    {
        $this->instance->setDefaultNeighbours(7);

        $pagination = $this->instance->create();

        self::assertSame(7, $pagination->neighbours);
    }

    #[Test]
    public function create_appliesDefaultTemplate(): void
    {
        $this->instance->setDefaultTemplate('pagination::default');

        $pagination = $this->instance->create();

        self::assertSame('pagination::default', $pagination->getTemplate());
    }

    #[Test]
    public function create_clampsNonPositiveCurrentPageToBasePage(): void
    {
        $pagination = $this->instance->create(0, 10);

        self::assertSame(1, $pagination->current);
    }

    #[Before]
    protected function prepareInstance(): void
    {
        $this->instance = new PaginationFactory(
            $this->createStub(Navigator::class),
            $this->createStub(SystemUri::class),
            $this->createStub(RendererService::class)
        );
    }
}
